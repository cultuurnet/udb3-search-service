<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\DateTimeFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar\DayOfWeekCounts;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar\EffectiveOpeningHours;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar\EffectiveOpeningHoursResolver;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use CultuurNet\UDB3\Search\Offer\TimeOfDay;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use stdClass;

final class CalendarTransformer implements JsonTransformer
{
    /**
     * List of countries that UDB3 supports and their timezones so we can index localTimeRange based on the start and
     * end times of an event converted to the timezone in which it takes place.
     * @see https://github.com/eggert/tz/blob/master/zone1970.tab
     */
    private const TIMEZONES = [
        'BE' => 'Europe/Brussels',
        'NL' => 'Europe/Amsterdam',
    ];
    private const DEFAULT_TIMEZONE = 'Europe/Brussels';

    private const STATUS_AVAILABLE = 'Available';

    private const BOOKING_AVAILABLE = 'Available';

    /**
     * Minimum number of effectively-open days a day of week must reach before it is indexed in
     * recurringOnDayOfWeek. At 4 an offer that runs less than a month never qualifies, keeping the
     * field to recurring offers.
     */
    private const RECURRING_ON_DAY_OF_WEEK_THRESHOLD = 4;

    private JsonTransformerLogger $logger;

    private EffectiveOpeningHoursResolver $effectiveOpeningHoursResolver;

    public function __construct(
        JsonTransformerLogger $logger,
        EffectiveOpeningHoursResolver $effectiveOpeningHoursResolver
    ) {
        $this->logger = $logger;
        $this->effectiveOpeningHoursResolver = $effectiveOpeningHoursResolver;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    public function transform(array $from, array $draft = []): array
    {
        // Index status and booking availability as Available by default,
        // even if there are errors like missing calendar type, missing subEvents, ...
        $draft['status'] = self::STATUS_AVAILABLE;
        $draft['bookingAvailability'] = self::BOOKING_AVAILABLE;

        $draft['hasOvernight'] = false;
        $draft['hasChildcare'] = false;
        $draft['recurringOnDayOfWeek'] = [];

        if (!isset($from['calendarType'])) {
            $this->logger->logMissingExpectedField('calendarType');
            return $draft;
        }

        $draft = $this->transformCalendarType($from, $draft);
        $draft = $this->transformStatus($from, $draft);
        $draft = $this->transformBookingAvailability($from, $draft);
        $draft = $this->transformHasOvernight($from, $draft);

        /*
        Read top-level hasChildcare before polyFillJsonLdSubEvents(), as the generated subEvents no longer contain a childcare key.
        Per-subEvent hasChildcare is computed later in transformSubEvents() from each subEvent's own childcare key.
        */
        $draft['hasChildcare'] = $this->determineHasChildcare($from);

        $effectiveOpeningHours = $this->resolveEffectiveOpeningHours($from);

        // Multiple calendars have no opening hours to resolve; their occurrences are the explicit
        // source sub-events, so their day of week counts are derived from those instead.
        $dayOfWeekCounts = $from['calendarType'] === 'multiple'
            ? $this->countDayOfWeekForMultiple($from)
            : $effectiveOpeningHours->dayCounts();
        $draft['recurringOnDayOfWeek'] = $this->determineRecurringOnDayOfWeek($dayOfWeekCounts);

        $from = $this->polyFillJsonLdSubEvents($from, $effectiveOpeningHours);
        if (!isset($from['subEvent'])) {
            $this->logger->logMissingExpectedField('subEvent');
            return $draft;
        }

        $from = $this->extendSubEventsWithChildcare($from);

        $draft = $this->transformDateRange($from, $draft);
        $draft = $this->transformLocalTimeRange($from, $draft);
        $draft = $this->transformSubEvents($from, $draft);
        return $draft;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     */
    private function resolveEffectiveOpeningHours(array $from): EffectiveOpeningHours
    {
        if ($from['calendarType'] === 'periodic' || $from['calendarType'] === 'permanent') {
            return $this->effectiveOpeningHoursResolver->resolve($from);
        }

        return EffectiveOpeningHours::empty();
    }

    /**
     * @return list<string>
     *   The days of week (monday..sunday) the offer occurs on at least
     *   RECURRING_ON_DAY_OF_WEEK_THRESHOLD days, in canonical week order. A day of week below the
     *   threshold is dropped rather than indexed.
     */
    private function determineRecurringOnDayOfWeek(DayOfWeekCounts $dayOfWeekCounts): array
    {
        return array_map(
            static fn (DayOfWeek $day): string => $day->value,
            $dayOfWeekCounts->daysWithMinimumCount(self::RECURRING_ON_DAY_OF_WEEK_THRESHOLD)
        );
    }

    /**
     * Counts, per day of week, the number of distinct days a "multiple" calendar occurs on, derived from
     * its explicit source sub-events. Each sub-event contributes every calendar day it spans (a Friday
     * to Sunday sub-event counts Friday, Saturday and Sunday), evaluated in the offer's local timezone
     * (the same one used for localTimeRange). It counts days, not slots: a date covered by more than
     * one sub-event counts once.
     *
     * @param array $from
     *   JSON-LD of an event, as an associative array
     */
    private function countDayOfWeekForMultiple(array $from): DayOfWeekCounts
    {
        $dayOfWeekCounts = new DayOfWeekCounts();

        $timezone = $this->determineLocalTimezone($from);
        $countedDates = [];

        foreach ($from['subEvent'] ?? [] as $subEvent) {
            if (!isset($subEvent['startDate'])) {
                // Missing startDates are logged when building dateRange.
                continue;
            }

            $startDate = DateTimeFactory::fromAtom($subEvent['startDate'])
                ->setTimezone($timezone)
                ->setTime(0, 0);

            // Fall back to a single day when the end date is missing or before the start.
            $endDate = isset($subEvent['endDate'])
                ? DateTimeFactory::fromAtom($subEvent['endDate'])->setTimezone($timezone)->setTime(0, 0)
                : $startDate;
            if ($endDate < $startDate) {
                $endDate = $startDate;
            }

            for ($date = $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
                $dateString = $date->format('Y-m-d');
                if (isset($countedDates[$dateString])) {
                    continue;
                }
                $countedDates[$dateString] = true;

                $dayOfWeekCounts = $dayOfWeekCounts->withIncremented(DayOfWeek::fromDate($date));
            }
        }

        return $dayOfWeekCounts;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformCalendarType(array $from, array $draft): array
    {
        $draft['calendarType'] = $from['calendarType'];
        return $draft;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformHasOvernight(array $from, array $draft): array
    {
        $draft['hasOvernight'] = $this->determineHasOvernight($from);
        return $draft;
    }

    /**
     * The search couples on event level: if at least one sub-event has overnight === true, the whole
     * offer is considered to have an overnight stay. A partial overnight event (some sub-events true,
     * some false) therefore counts as having overnight.
     *
     * @param array $from
     *   JSON-LD of an event or place, as an associative array. Read before subEvents are poly-filled
     *   from openingHours; overnight only ever lives on the explicit source subEvents of single and
     *   multiple calendars.
     * @return bool
     *   True if at least one source subEvent is flagged as overnight.
     */
    private function determineHasOvernight(array $from): bool
    {
        foreach ($from['subEvent'] ?? [] as $subEvent) {
            if (($subEvent['overnight'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformDateRange(array $from, array $draft): array
    {
        $dateRange = $this->convertSubEventsToDateRanges($from['subEvent']);

        // Even though there's a subEvent, it might not have a startDate and/or endDate if the data is incorrect so it's
        // still possible we end up without date ranges.
        if (!empty($dateRange)) {
            $draft['dateRange'] = $dateRange;
        }

        return $draft;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformLocalTimeRange(array $from, array $draft): array
    {
        $localTimeRange = $this->convertSubEventsToLocalTimeRanges(
            $from['subEvent'],
            $this->determineLocalTimezone($from)
        );

        // Even though there's a subEvent, it might not have a startDate and/or endDate if the data is incorrect so it's
        // still possible we end up without time ranges.
        if (!empty($localTimeRange)) {
            $draft['localTimeRange'] = $localTimeRange;
        }

        return $draft;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformStatus(array $from, array $draft): array
    {
        $status = $this->determineStatus($from);
        $draft['status'] = $status;
        return $draft;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformBookingAvailability(array $from, array $draft): array
    {
        $bookingAvailability = $this->determineBookingAvailability($from);
        $draft['bookingAvailability'] = $bookingAvailability;
        return $draft;
    }

    private function determineHasChildcare(array $from): bool
    {
        foreach ($from['subEvent'] ?? [] as $subEvent) {
            if (isset($subEvent['childcare'])) {
                return true;
            }
        }

        foreach ($from['openingHours'] ?? [] as $openingHour) {
            if (isset($openingHour['childcare'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @param array $draft
     *   JSON to index in Elasticsearch so far, as an associative array
     * @return array
     *   Updated JSON to index in Elasticsearch, as an associative array
     */
    private function transformSubEvents(array $from, array $draft): array
    {
        $draft['subEvent'] = [];

        foreach ($from['subEvent'] as $subEvent) {
            // Skip inverted ranges; already logged when building dateRange.
            if (!$this->isValidDateRange($subEvent)) {
                continue;
            }

            $localTimeRange = $this->convertSubEventToLocalTimeRanges($subEvent, $this->determineLocalTimezone($from));
            if (count($localTimeRange) === 1) {
                $localTimeRange = $localTimeRange[0];
            }

            $draft['subEvent'][] = [
                'dateRange' => $this->convertSubEventToDateRange($subEvent),
                'localTimeRange' => $localTimeRange,
                'status' => $this->determineStatus($subEvent, $from),
                'bookingAvailability' => $this->determineBookingAvailability($subEvent, $from),
                'hasChildcare' => isset($subEvent['childcare']),
                'hasOvernight' => ($subEvent['overnight'] ?? false) === true,
            ];
        }

        return $draft;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place, as an associative array
     * @return array
     *   Given JSON-LD, with an additional subEvent property if there was none and it could be derived from the
     *   following logic:
     *     - calendar type single: add a single subEvent based on startDate and endDate
     *     - calendar type multiple: can not (and should not) be poly-filled if missing
     *     - calendar type periodic: add subEvents based on opening hours, or if there are no opening hours based on
     *         startDate and endDate
     *     - calendar type permanent: add subEvents based on opening hours, or a single subEvent with an unlimited range
     *         if there are no opening hours
     */
    private function polyFillJsonLdSubEvents(array $from, EffectiveOpeningHours $effectiveOpeningHours): array
    {
        if ($from['calendarType'] === 'single' || $from['calendarType'] === 'periodic') {
            if (!isset($from['startDate'])) {
                $this->logger->logMissingExpectedField('startDate');
                return $from;
            }

            if (!isset($from['endDate'])) {
                $this->logger->logMissingExpectedField('endDate');
                return $from;
            }
        }

        switch ($from['calendarType']) {
            case 'single':
                return $this->polyFillJsonLdSubEventsFromStartAndEndDate($from);

            case 'multiple':
                return $from;

            case 'periodic':
                if (isset($from['openingHours'])) {
                    return $this->polyFillJsonLdSubEventsFromOpeningHours($from, $effectiveOpeningHours);
                }
                return $this->polyFillJsonLdSubEventsFromStartAndEndDate($from);

            case 'permanent':
                if (isset($from['openingHours'])) {
                    return $this->polyFillJsonLdSubEventsFromOpeningHours($from, $effectiveOpeningHours);
                }
                $from['subEvent'] = [
                    [
                        '@type' => 'Event',
                        'startDate' => null,
                        'endDate' => null,
                    ],
                ];
                return $from;

            default:
                $this->logger->logWarning(
                    "Could not polyfill subEvent for unknown calendarType '{$from['calendarType']}'."
                );
                return $from;
        }
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place with startDate and endDate properties, as an associative array
     * @return array
     *   Given JSON-LD with an additional subEvent property based on the startDate and endDate properties
     */
    private function polyFillJsonLdSubEventsFromStartAndEndDate(array $from): array
    {
        if (isset($from['subEvent'])) {
            return $from;
        }

        $from['subEvent'] = [
            [
                '@type' => 'Event',
                'startDate' => $from['startDate'],
                'endDate' => $from['endDate'],
            ],
        ];

        return $from;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place with openingHours property, as an associative array
     * @return array
     *   Given JSON-LD poly-filled with a subEvent property based on the openingHours property
     */
    private function polyFillJsonLdSubEventsFromOpeningHours(
        array $from,
        EffectiveOpeningHours $effectiveOpeningHours
    ): array {
        $subEvent = [];

        foreach ($effectiveOpeningHours->slots() as $slot) {
            $subEventStartDate = new DateTimeImmutable(
                $slot['date']->format('Y-m-d') . 'T' . $slot['opens'] . ':00',
                $this->determineLocalTimezone($from)
            );

            $subEventEndDate = new DateTimeImmutable(
                $slot['date']->format('Y-m-d') . 'T' . $slot['closes'] . ':00',
                $this->determineLocalTimezone($from)
            );

            $subEvent[] = [
                '@type' => 'Event',
                'startDate' => $subEventStartDate->format(DateTime::ATOM),
                'endDate' => $subEventEndDate->format(DateTime::ATOM),
            ];
        }

        if (!empty($subEvent)) {
            $from['subEvent'] = $subEvent;
        }

        return $from;
    }

    /**
     * A child is present for the childcare hours as well, so a sub-event lasts from the start of its
     * childcare until the end of it. Widening it here, before dateRange, localTimeRange and
     * subEvent[] are built from it, is what makes a slot that only overlaps childcare match.
     *
     * Sub-events generated from opening hours drop the childcare range, so periodic and permanent
     * calendars pass through unchanged.
     */
    private function extendSubEventsWithChildcare(array $from): array
    {
        $timezone = $this->determineLocalTimezone($from);

        foreach ($from['subEvent'] as $index => $subEvent) {
            if (!isset($subEvent['childcare'])) {
                continue;
            }

            $from['subEvent'][$index] = $this->extendSubEventWithChildcare($subEvent, $timezone);
        }

        return $from;
    }

    private function extendSubEventWithChildcare(array $subEvent, DateTimeZone $timezone): array
    {
        $startDate = $this->parseSubEventDate($subEvent['startDate'] ?? null, $timezone);
        $endDate = $this->parseSubEventDate($subEvent['endDate'] ?? null, $timezone);

        $childcareStart = $this->atTimeOfDay($startDate, $subEvent['childcare']['start'] ?? null);
        $childcareEnd = $this->atTimeOfDay($endDate, $subEvent['childcare']['end'] ?? null);

        // Entry API already guarantees this. Guarded anyway because an inverted range is dropped by
        // isValidDateRange(), which would lose the sub-event without any sign of it.
        if ($childcareStart !== null && $childcareStart < $startDate) {
            $subEvent['startDate'] = $childcareStart->format(DateTime::ATOM);
        }

        if ($childcareEnd !== null && $childcareEnd > $endDate) {
            $subEvent['endDate'] = $childcareEnd->format(DateTime::ATOM);
        }

        return $subEvent;
    }

    private function parseSubEventDate(?string $date, DateTimeZone $timezone): ?DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        try {
            return DateTimeFactory::fromAtom($date)->setTimezone($timezone);
        } catch (InvalidArgumentException $exception) {
            // Already reported when building dateRange.
            return null;
        }
    }

    private function atTimeOfDay(?DateTimeImmutable $date, ?string $timeOfDay): ?DateTimeImmutable
    {
        if ($date === null || $timeOfDay === null) {
            return null;
        }

        $parsed = TimeOfDay::tryFromString($timeOfDay);
        if ($parsed === null) {
            $this->logger->logWarning("Unknown childcare time '{$timeOfDay}'.");
            return null;
        }

        return $parsed->on($date);
    }

    /**
     * @param array $subEvents
     *   subEvent property on event/place JSON-LD, decoded as an array of arrays with "startDate", "endDate", ... each.
     * @return stdClass[]
     *   List of Elasticsearch range objects
     */
    private function convertSubEventsToDateRanges(array $subEvents): array
    {
        $dateRanges = [];

        foreach ($subEvents as $index => $subEvent) {
            if (!array_key_exists('startDate', $subEvent)) {
                $this->logger->logMissingExpectedField("subEvent[{$index}].startDate");
                continue;
            }

            if (!array_key_exists('endDate', $subEvent)) {
                $this->logger->logMissingExpectedField("subEvent[{$index}].endDate");
                continue;
            }

            if (!$this->isValidDateRange($subEvent)) {
                $this->logger->logWarning("subEvent[{$index}] skipped: start date is after end date.");
                continue;
            }

            $dateRanges[] = $this->convertSubEventToDateRange($subEvent);
        }

        return $dateRanges;
    }

    /**
     * @param array $subEvent
     *   JSON-LD of a single subEvent, as an associative array
     */
    private function convertSubEventToDateRange(array $subEvent): stdClass
    {
        return (object) [
            'gte' => $subEvent['startDate'] ?? null,
            'lte' => $subEvent['endDate'] ?? null,
        ];
    }

    /**
     * @param array $subEvents
     *   subEvent property on event/place JSON-LD, decoded as an array of arrays with "startDate", "endDate", ... each.
     * @return stdClass[]
     *   A flattened list of Elasticsearch range objects constructed by convertSubEventToLocalTimeRanges() for each
     *   subEvent. Duplicates are omitted.
     */
    private function convertSubEventsToLocalTimeRanges(array $subEvents, DateTimeZone $timezone): array
    {
        $timeRanges = [];

        foreach ($subEvents as $subEvent) {
            if (!array_key_exists('startDate', $subEvent)) {
                // Logged already when creating dateRange
                continue;
            }

            if (!array_key_exists('endDate', $subEvent)) {
                // Logged already when creating dateRange
                continue;
            }

            if (!$this->isValidDateRange($subEvent)) {
                // Logged already when creating dateRange
                continue;
            }

            $localTimeRangesForSubEvent = $this->convertSubEventToLocalTimeRanges($subEvent, $timezone);

            // Reduce unnecessary duplicates in the top level localTimeRange.
            // This reduces a lot of duplicates for events with opening hours for example, because when we drop the
            // date info we don't need the same opening hours for _every_ week like we do for dates.
            foreach ($localTimeRangesForSubEvent as $localTimeRangeForSubEvent) {
                if (!in_array($localTimeRangeForSubEvent, $timeRanges, false)) {
                    $timeRanges[] = $localTimeRangeForSubEvent;
                }
            }
        }

        return array_values($timeRanges);
    }

    /**
     * @param array $subEvent
     *   JSON-LD of a single subEvent, as an associative array
     * @return stdClass[]
     *   Elasticsearch range objects. Can be multiple when the startDate and endDate are on different days.
     */
    private function convertSubEventToLocalTimeRanges(array $subEvent, DateTimeZone $timezone): array
    {
        $startDate = null;
        $endDate = null;

        $startTime = null;
        $endTime = null;

        // When converting the dates to times it's important we set the right timezone, because sometimes the dates are
        // in UTC for example and then the time info is not what we'd expect to be in Belgium.
        if (isset($subEvent['startDate'])) {
            $startDate = DateTimeFactory::fromAtom($subEvent['startDate']);
            $startDate = $startDate->setTimezone($timezone);
            $startTime = $startDate->format('Hi');
        }

        if (isset($subEvent['endDate'])) {
            $endDate = DateTimeFactory::fromAtom($subEvent['endDate']);
            $endDate = $endDate->setTimezone($timezone);
            $endTime = $endDate->format('Hi');
        }

        if ($startDate && $endDate) {
            $startDateWithoutHours = $startDate->setTime(0, 0, 0);
            $endDateWithoutHours = $endDate->setTime(0, 0, 0);
            $daySpan = $endDateWithoutHours->diff($startDateWithoutHours)->days;

            // Start and end time are on the same day, so we have one time range.
            if ($daySpan === 0) {
                return [
                    (object) [
                        'gte' => $startTime,
                        'lte' => $endTime,
                    ],
                ];
            }

            // End time is on the day after the start time. To prevent invalid ranges where the end time is lower than
            // the start time, we make ranges from start -> 23:59 and from 00:00 -> end.
            if ($daySpan === 1) {
                return [
                    (object) [
                        'gte' => $startTime,
                        'lte' => 2359,
                    ],
                    (object) [
                        'gte' => 0000,
                        'lte' => $endTime,
                    ],
                ];
            }

            // End time is multiple days after start time. Same as the day after above, but with a complete range
            // in-between. If there's more than 1 day in-between, one complete range is still sufficient.
            return [
                (object) [
                    'gte' => $startTime,
                    'lte' => 2359,
                ],
                (object) [
                    'gte' => 0000,
                    'lte' => 2359,
                ],
                (object) [
                    'gte' => 0000,
                    'lte' => $endTime,
                ],
            ];
        }

        if ($startDate) {
            return [
                (object) [
                    'gte' => $startTime,
                    'lte' => null,
                ],
            ];
        }

        if ($endDate) {
            return [
                (object) [
                    'gte' => null,
                    'lte' => $endTime,
                ],
            ];
        }

        return [
            (object) [
                'gte' => null,
                'lte' => null,
            ],
        ];
    }

    /**
     * @param array $entity
     *   JSON-LD of an event, place, or subEvent as an associative array
     * @param array|null $parent
     *   If the given $entity is a subEvent, the JSON-LD of the parent event/place can be given as an associative array
     *   to use as a fallback if the subEvent has no explicit status but the parent does
     */
    private function determineStatus(array $entity, ?array $parent = null): string
    {
        // If the given event, subEvent, or place has a status.type, use that.
        if (isset($entity['status']['type'])) {
            return $entity['status']['type'];
        }

        // Some events/places have an older projection with just a status property that is a string instead of
        // status.type and status.reason on their top-level. In that case, use that.
        if (isset($entity['status']) && is_string($entity['status'])) {
            return $entity['status'];
        }

        // If we still haven't found a status and there's a parent event/place, use that one's status.
        if ($parent !== null) {
            return $this->determineStatus($parent);
        }

        // If there's still no status found assume it's Available.
        return self::STATUS_AVAILABLE;
    }

    /**
     * @param array $entity
     *   JSON-LD of an event, place, or subEvent as an associative array
     * @param array|null $parent
     *   If the given $entity is a subEvent, the JSON-LD of the parent event/place can be given as an associative array
     *   to use as a fallback if the subEvent has no explicit booking availability but the parent does
     */
    private function determineBookingAvailability(array $entity, ?array $parent = null): string
    {
        if (isset($entity['bookingAvailability']['type'])) {
            return $entity['bookingAvailability']['type'];
        }

        if ($parent !== null) {
            return $this->determineBookingAvailability($parent);
        }

        return self::BOOKING_AVAILABLE;
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place as an associative array
     */
    private function determineLocalTimezone(array $from): DateTimeZone
    {
        $location = $from['location'] ?? $from;
        $country = $location['address']['addressCountry'] ?? null;

        if ($country) {
            return new DateTimeZone(self::TIMEZONES[$country] ?? self::DEFAULT_TIMEZONE);
        }
        return new DateTimeZone(self::DEFAULT_TIMEZONE);
    }

    /**
     * Detects sub-events whose startDate is strictly after endDate, which produce inverted
     * ranges that ES8 rejects (e.g. a 20:00–02:00 opening hour stored on the same calendar day).
     * Returns true (i.e. "do not skip") when either date is missing or unparseable; those cases
     * are handled by the array_key_exists/logging checks at the call sites.
     *
     * @see https://jira.publiq.be/browse/III-7275
     */
    private function isValidDateRange(array $subEvent): bool
    {
        $start = DateTimeImmutable::createFromFormat(DateTime::ATOM, $subEvent['startDate'] ?? '');
        $end = DateTimeImmutable::createFromFormat(DateTime::ATOM, $subEvent['endDate'] ?? '');

        if ($start === false || $end === false) {
            // Missing or unparseable dates are handled by existing checks elsewhere.
            return true;
        }

        return $start <= $end;
    }
}

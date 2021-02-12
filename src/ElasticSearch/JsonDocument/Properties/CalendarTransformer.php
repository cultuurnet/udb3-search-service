<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
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

    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    public function __construct(JsonTransformerLogger $logger)
    {
        $this->logger = $logger;
    }

    public function transform(array $from, array $draft = []): array
    {
        // Index status Available by default even if there are errors like missing calendar type, missing subEvents, ...
        $draft['status'] = self::STATUS_AVAILABLE;

        if (!isset($from['calendarType'])) {
            $this->logger->logMissingExpectedField('calendarType');
            return $draft;
        }

        $draft = $this->transformCalendarType($from, $draft);
        $draft = $this->transformStatus($from, $draft);

        $from = $this->polyFillJsonLdSubEvents($from);
        if (!isset($from['subEvent'])) {
            $this->logger->logMissingExpectedField('subEvent');
            return $draft;
        }

        $draft = $this->transformDateRange($from, $draft);
        $draft = $this->transformLocalTimeRange($from, $draft);
        $draft = $this->transformSubEvents($from, $draft);
        return $draft;
    }

    private function transformCalendarType(array $from, array $draft): array
    {
        $draft['calendarType'] = $from['calendarType'];
        return $draft;
    }

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

    private function transformStatus(array $from, array $draft): array
    {
        $status = $this->determineStatus($from);
        $draft['status'] = $status;
        return $draft;
    }

    private function transformSubEvents(array $from, array $draft): array
    {
        $draft['subEvent'] = [];

        foreach ($from['subEvent'] as $subEvent) {
            $localTimeRange = $this->convertSubEventToLocalTimeRanges($subEvent, $this->determineLocalTimezone($from));
            if (count($localTimeRange) === 1) {
                $localTimeRange = $localTimeRange[0];
            }

            $draft['subEvent'][] = [
                'dateRange' => $this->convertSubEventToDateRange($subEvent),
                'localTimeRange' => $localTimeRange,
                'status' => $this->determineStatus($subEvent, $from),
            ];
        }

        return $draft;
    }

    private function polyFillJsonLdSubEvents(array $from): array
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
                break;

            case 'multiple':
                return $from;
                break;

            case 'periodic':
                if (isset($from['openingHours'])) {
                    return $this->polyFillJsonLdSubEventsFromOpeningHours($from);
                }
                return $this->polyFillJsonLdSubEventsFromStartAndEndDate($from);
                break;

            case 'permanent':
                if (isset($from['openingHours'])) {
                    return $this->polyFillJsonLdSubEventsFromOpeningHours($from);
                }
                $from['subEvent'] = [
                    [
                        '@type' => 'Event',
                        'startDate' => null,
                        'endDate' => null,
                        'status' => [
                            'type' => $this->determineStatus($from),
                        ],
                    ],
                ];
                return $from;
                break;

            default:
                $this->logger->logWarning(
                    "Could not polyfill subEvent for unknown calendarType '{$from['calendarType']}'."
                );
                return $from;
                break;
        }
    }

    private function polyFillJsonLdSubEventsFromStartAndEndDate(array $from): array
    {
        $from['subEvent'] = [
            [
                '@type' => 'Event',
                'startDate' => $from['startDate'],
                'endDate' => $from['endDate'],
                'status' => [
                    'type' => $this->determineStatus($from),
                ],
            ],
        ];

        return $from;
    }

    private function polyFillJsonLdSubEventsFromOpeningHours(array $from): array
    {
        $openingHoursByDay = $this->convertOpeningHoursToListGroupedByDay($from['openingHours']);

        if ($from['calendarType'] === 'permanent') {
            $now = new Chronos();
            $startDate = $now->modify('-6 months');
            $endDate = $now->modify('+12 months');
        } else {
            $startDate = Chronos::createFromFormat(DateTime::ATOM, $from['startDate']);
            $endDate = Chronos::createFromFormat(DateTime::ATOM, $from['endDate']);
        }

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        $subEvent = [];

        // In case of sub events based on opening hours, the status should always be the same as the on one the parent
        // event/place.
        $subEventStatusType = $this->determineStatus($from);

        /* @var DateTime $date */
        foreach ($period as $date) {
            $day = strtolower($date->format('l'));

            foreach ($openingHoursByDay[$day] as $openingHours) {
                $subEventStartDate = new DateTimeImmutable(
                    $date->format('Y-m-d') . 'T' . $openingHours['opens'] . ':00',
                    $this->determineLocalTimezone($from)
                );

                $subEventEndDate = new DateTimeImmutable(
                    $date->format('Y-m-d') . 'T' . $openingHours['closes'] . ':00',
                    $this->determineLocalTimezone($from)
                );

                $subEvent[] = [
                    '@type' => 'Event',
                    'startDate' => $subEventStartDate->format(DateTime::ATOM),
                    'endDate' => $subEventEndDate->format(DateTime::ATOM),
                    'status' => [
                        'type' => $subEventStatusType,
                    ],
                ];
            }
        }

        if (!empty($subEvent)) {
            $from['subEvent'] = $subEvent;
        }

        return $from;
    }

    private function convertOpeningHoursToListGroupedByDay(array $openingHours): array
    {
        $openingHoursByDay = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        foreach ($openingHours as $index => $openingHoursEntry) {
            if (!isset($openingHoursEntry['dayOfWeek'])) {
                $this->logger->logMissingExpectedField("openingHours[{$index}].dayOfWeek");
                continue;
            }

            if (!isset($openingHoursEntry['opens'])) {
                $this->logger->logMissingExpectedField("openingHours[{$index}].opens");
                continue;
            }

            if (!isset($openingHoursEntry['closes'])) {
                $this->logger->logMissingExpectedField("openingHours[{$index}].closes");
                continue;
            }

            foreach ($openingHoursEntry['dayOfWeek'] as $day) {
                if (!array_key_exists($day, $openingHoursByDay)) {
                    $this->logger->logWarning("Unknown day '{$day}' in opening hours.");
                    continue;
                }

                $openingHoursByDay[$day][] = [
                    'opens' => $openingHoursEntry['opens'],
                    'closes' => $openingHoursEntry['closes'],
                ];
            }
        }

        foreach ($openingHoursByDay as $day => &$openingHoursForSpecificDay) {
            sort($openingHoursForSpecificDay);
        }

        return $openingHoursByDay;
    }

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

            $dateRanges[] = $this->convertSubEventToDateRange($subEvent);
        }

        return $dateRanges;
    }

    private function convertSubEventToDateRange(array $subEvent): stdClass
    {
        // Convert to an object so that if both gte and lte are left out (because there's no startDate and no endDate,
        // like for permanent events, then we need to make sure we send an object like {} to Elasticsearch. An empty
        // PHP array would get converted to [] in JSON.
        return (object) array_filter(
            [
                'gte' => $subEvent['startDate'] ?? null,
                'lte' => $subEvent['endDate'] ?? null,
            ]
        );
    }

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
     * @return stdClass[]
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
            $startDate = DateTimeImmutable::createFromFormat(DateTime::ATOM, $subEvent['startDate']);
            $startDate = $startDate->setTimezone($timezone);
            $startTime = $startDate->format('Hi');
        }

        if (isset($subEvent['endDate'])) {
            $endDate = DateTimeImmutable::createFromFormat(DateTime::ATOM, $subEvent['endDate']);
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
            return [(object) ['gte' => $startTime]];
        }

        if ($endDate) {
            return [(object) ['lte' => $endTime]];
        }

        // We need to make sure we send an object like {} to Elasticsearch if there's no start or end time.
        // [[]] would be converted to [[]] in JSON, while we want [{}].
        return [new stdClass()];
    }

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

    private function determineLocalTimezone(array $from): DateTimeZone
    {
        $location = $from['location'] ?? $from;
        $country = $location['address']['addressCountry'] ?? null;

        if ($country) {
            return new DateTimeZone(self::TIMEZONES[$country] ?? self::DEFAULT_TIMEZONE);
        }
        return new DateTimeZone(self::DEFAULT_TIMEZONE);
    }
}

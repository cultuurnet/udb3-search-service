<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use Psr\Log\NullLogger;
use stdClass;

final class CalendarTransformer implements JsonTransformer
{
    private const STATUS_AVAILABLE = 'Available';
    private const STATUS_UNAVAILABLE = 'Unavailable';
    private const STATUS_TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';

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
        $draft = $this->transformCalendarType($from, $draft);
        $draft = $this->transformDateRange($from, $draft);
        return $draft;
    }

    private function transformCalendarType(array $from, array $draft): array
    {
        if (!isset($from['calendarType'])) {
            $this->logger->logMissingExpectedField('calendarType');
            return $draft;
        }

        $draft['calendarType'] = $from['calendarType'];
        return $draft;
    }

    private function transformDateRange(array $from, array $draft): array
    {
        if (!isset($from['calendarType'])) {
            // Logged in transformCalendarType().
            return $draft;
        }

        $from = $this->polyFillJsonLdSubEvents($from);

        $dateRange = [];
        $availableDateRange = [];
        $unavailableDateRange = [];
        $temporarilyUnavailableDateRange = [];

        if (isset($from['subEvent'])) {
            // Index each subEvent as a separate date range.
            $dateRange = $this->convertSubEventsToDateRanges($from['subEvent']);

            // Index each subEvent as a separate date range in the correct collection of date ranges for the subEvent's
            // status.
            $availableDateRange = $this->convertSubEventsToDateRanges(
                $this->filterSubEventsByStatusType($from['subEvent'], 'Available'),
                true
            );
            $unavailableDateRange = $this->convertSubEventsToDateRanges(
                $this->filterSubEventsByStatusType($from['subEvent'], 'Unavailable'),
                true
            );
            $temporarilyUnavailableDateRange = $this->convertSubEventsToDateRanges(
                $this->filterSubEventsByStatusType($from['subEvent'], 'TemporarilyUnavailable'),
                true
            );
        } elseif (!isset($from['subEvent']) && $from['calendarType'] === 'permanent') {
            // Index a single range without any bounds.
            $dateRange = [new stdClass()];

            // Index a single range without any bounds for the status of the event/place.
            $status = $this->determineStatus($from);
            $availableDateRange = $status === self::STATUS_AVAILABLE ? [new stdClass()] : [];
            $unavailableDateRange = $status === self::STATUS_UNAVAILABLE ? [new stdClass()] : [];
            $temporarilyUnavailableDateRange = $status === self::STATUS_TEMPORARILY_UNAVAILABLE ? [new stdClass()] : [];
        } else {
            $this->logger->logMissingExpectedField('subEvent');
            return $draft;
        }

        $ranges = array_filter(
            [
                'dateRange' => $dateRange,
                'availableDateRange' => $availableDateRange,
                'unavailableDateRange' => $unavailableDateRange,
                'temporarilyUnavailableDateRange' => $temporarilyUnavailableDateRange,
            ],
            function (array $values) {
                return count($values);
            }
        );

        $draft = array_merge($draft, $ranges);

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
                    new \DateTimeZone('Europe/Brussels')
                );

                $subEventEndDate = new DateTimeImmutable(
                    $date->format('Y-m-d') . 'T' . $openingHours['closes'] . ':00',
                    new \DateTimeZone('Europe/Brussels')
                );

                $subEvent[] = [
                    '@type' => 'Event',
                    'startDate' => $subEventStartDate->format(DateTime::ATOM),
                    'endDate' => $subEventEndDate->format(DateTime::ATOM),
                    'status' => [
                        'type' => $subEventStatusType,
                    ]
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

    private function filterSubEventsByStatusType(array $subEvents, string $expectedStatusType): array
    {
        return array_filter(
            $subEvents,
            function (array $subEvent) use ($expectedStatusType) {
                $actualStatusType = $this->determineStatus($subEvent);
                return $actualStatusType === $expectedStatusType;
            }
        );
    }

    private function convertSubEventsToDateRanges(array $subEvents, bool $disableLogging = false): array
    {
        $dateRanges = [];
        $logger = $disableLogging ? new JsonTransformerPsrLogger(new NullLogger()) : $this->logger;

        foreach ($subEvents as $index => $subEvent) {
            if (!isset($subEvent['startDate'])) {
                $logger->logMissingExpectedField("subEvent[{$index}].startDate");
                continue;
            }

            if (!isset($subEvent['endDate'])) {
                $logger->logMissingExpectedField("subEvent[{$index}].endDate");
                continue;
            }

            $dateRanges[] = [
                'gte' => $subEvent['startDate'],
                'lte' => $subEvent['endDate'],
            ];
        }

        return $dateRanges;
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
        return 'Available';
    }
}

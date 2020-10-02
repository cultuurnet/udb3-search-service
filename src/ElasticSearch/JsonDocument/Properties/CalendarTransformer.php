<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use stdClass;

final class CalendarTransformer implements JsonTransformer
{
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

        if (isset($from['subEvent'])) {
            // Index each subEvent as a separate date range.
            $dateRange = $this->convertSubEventsToDateRanges($from['subEvent']);
        } elseif (!isset($from['subEvent']) && $from['calendarType'] === 'permanent') {
            // Index a single range without any bounds.
            $dateRange = [[]];
        } else {
            $this->logger->logMissingExpectedField('subEvent');
            $dateRange = [];
        }

        if (!empty($dateRange)) {
            $draft['dateRange'] = $dateRange;
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
            if (!isset($subEvent['startDate'])) {
                $this->logger->logMissingExpectedField("subEvent[{$index}].startDate");
                continue;
            }

            if (!isset($subEvent['endDate'])) {
                $this->logger->logMissingExpectedField("subEvent[{$index}].endDate");
                continue;
            }

            $dateRanges[] = [
                'gte' => $subEvent['startDate'],
                'lte' => $subEvent['endDate'],
            ];
        }

        return $dateRanges;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;

final class EffectiveOpeningHoursResolver
{
    public function __construct(private readonly JsonTransformerLogger $logger)
    {
    }

    /**
     * @param array $from
     *   JSON-LD of an event or place with an openingHours property, as an associative array
     */
    public function resolve(array $from): EffectiveOpeningHours
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

        $slots = [];

        /* @var DateTime $date */
        foreach ($period as $date) {
            foreach ($this->getEffectiveOpeningHoursOnDay($date, $from, $openingHoursByDay) as $openingHours) {
                $slots[] = [
                    'date' => $date,
                    'opens' => $openingHours['opens'],
                    'closes' => $openingHours['closes'],
                ];
            }
        }

        return new EffectiveOpeningHours($slots);
    }

    /**
     * @param array $openingHours
     *   JSON-LD of the openingHours property of an event/place, as an associative array
     * @return array<string, array<int<0, max>, array<string, mixed>>>
     *   Associative arrays with "opens" and "closes" keys with string values each, grouped in lists per weekday in an
     *   enclosing array
     */
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

        foreach ($openingHoursByDay as &$openingHoursForSpecificDay) {
            sort($openingHoursForSpecificDay);
        }

        return $openingHoursByDay;
    }

    /**
     * @param DateTimeInterface $date
     *   The date to check.
     * @param array $from
     *   JSON-LD of the event/place, as an associative array. May contain an "openingHoursClosedDays" key with a list
     *   of closed-day ranges, each having "startDate" and "endDate" as plain Y-m-d strings.
     * @return bool
     *   True if the given date falls within any closed-day range, false otherwise.
     */
    private function isClosedDay(DateTimeInterface $date, array $from): bool
    {
        $dateString = $date->format('Y-m-d');
        foreach ($from['openingHoursClosedDays'] ?? [] as $index => $closedDay) {
            if (!array_key_exists('startDate', $closedDay)) {
                $this->logger->logMissingExpectedField("openingHoursClosedDays[{$index}].startDate");
                continue;
            }

            if (!array_key_exists('endDate', $closedDay)) {
                $this->logger->logMissingExpectedField("openingHoursClosedDays[{$index}].endDate");
                continue;
            }

            if ($dateString >= $closedDay['startDate'] && $dateString <= $closedDay['endDate']) {
                return true;
            }
        }

        return false;
    }

    private function getEffectiveOpeningHoursOnDay(DateTimeInterface $date, array $from, array $regularOpeningHoursByDay): array
    {
        if ($this->isClosedDay($date, $from)) {
            return [];
        }

        $dayOfWeek = strtolower($date->format('l'));
        $adjustedDay = $this->findAdjustedDay($date, $from);

        // Adjusted entries fully replace regular hours; days not listed in the entry's openingHours are treated as closed.
        if ($adjustedDay !== null && isset($adjustedDay['openingHours'])) {
            return $this->convertOpeningHoursToListGroupedByDay($adjustedDay['openingHours'])[$dayOfWeek];
        }

        return $regularOpeningHoursByDay[$dayOfWeek];
    }

    private function findAdjustedDay(DateTimeInterface $date, array $from): ?array
    {
        $dateString = $date->format('Y-m-d');
        foreach ($from['openingHoursAdjustedDays'] ?? [] as $adjustedDay) {
            if ($dateString >= $adjustedDay['startDate'] && $dateString <= $adjustedDay['endDate']) {
                return $adjustedDay;
            }
        }
        return null;
    }
}

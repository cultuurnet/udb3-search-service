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
     * Resolves the effective (closures/adjustments applied) opening hours of an event or place over
     * its window. Always returns an {@see EffectiveOpeningHours}: when there are no usable opening
     * hours — no regular or adjusted hours to open a day, or a periodic calendar without a start/end
     * date to bound the window — it returns {@see EffectiveOpeningHours::empty()}, which is a truthful
     * "no effective opening hours" result, not an error condition.
     *
     * @param array $from
     *   JSON-LD of an event or place, as an associative array. Expected to have a calendarType; may
     *   have an openingHours property, openingHoursAdjustedDays and (for periodic) startDate/endDate.
     */
    public function resolve(array $from): EffectiveOpeningHours
    {
        $openingHours = $from['openingHours'] ?? [];

        // Only regular opening hours and adjusted days can open a day; closed days merely remove.
        // With neither there is nothing to resolve, so skip walking the (possibly long) window.
        if ($openingHours === [] && ($from['openingHoursAdjustedDays'] ?? []) === []) {
            return EffectiveOpeningHours::empty();
        }

        $openingHoursByDay = $this->convertOpeningHoursToListGroupedByDay($openingHours);

        if (($from['calendarType'] ?? null) === 'permanent') {
            $now = new Chronos();
            $startDate = $now->modify('-6 months');
            $endDate = $now->modify('+12 months');
        } elseif (isset($from['startDate'], $from['endDate'])) {
            $startDate = Chronos::createFromFormat(DateTime::ATOM, $from['startDate']);
            $endDate = Chronos::createFromFormat(DateTime::ATOM, $from['endDate']);
        } else {
            return EffectiveOpeningHours::empty();
        }

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        $slots = [];
        $dayCounts = [
            'monday' => 0,
            'tuesday' => 0,
            'wednesday' => 0,
            'thursday' => 0,
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0,
        ];

        /* @var DateTime $date */
        foreach ($period as $date) {
            $effectiveOpeningHoursOnDay = $this->getEffectiveOpeningHoursOnDay($date, $from, $openingHoursByDay);

            // Count days (not slots): a weekday with multiple opening-hour slots on the same date counts once.
            if (!empty($effectiveOpeningHoursOnDay)) {
                $dayCounts[strtolower($date->format('l'))]++;
            }

            foreach ($effectiveOpeningHoursOnDay as $openingHours) {
                $slots[] = [
                    'date' => $date,
                    'opens' => $openingHours['opens'],
                    'closes' => $openingHours['closes'],
                ];
            }
        }

        return new EffectiveOpeningHours($slots, [
            'monday' => $dayCounts['monday'],
            'tuesday' => $dayCounts['tuesday'],
            'wednesday' => $dayCounts['wednesday'],
            'thursday' => $dayCounts['thursday'],
            'friday' => $dayCounts['friday'],
            'saturday' => $dayCounts['saturday'],
            'sunday' => $dayCounts['sunday'],
        ]);
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

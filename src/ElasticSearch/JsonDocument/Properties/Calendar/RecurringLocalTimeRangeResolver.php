<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use CultuurNet\UDB3\Search\DateTimeFactory;
use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Which local times an offer weekly recurs on, per day of week.
 *
 * 1. Cut every sub-event into calendar days. Saturday 20:00 to Sunday 02:00 becomes Saturday
 *    20:00-24:00 and Sunday 00:00-02:00.
 * 2. Mark the quarters of an hour each piece occupies, rounded outward, per date. 10:05-11:50
 *    occupies 10:00 to 12:00. Per date, so two overlapping sub-events on one Wednesday count once.
 * 3. Count on how many dates each quarter was occupied. Four Wednesdays of 10:00-12:00 gives those
 *    quarters a count of four.
 * 4. Keep the quarters reaching the minimum and join the runs into ranges. Half open, so an
 *    activity ending at 12:00 does not answer a search starting at 12:00.
 */
final class RecurringLocalTimeRangeResolver
{
    private const MINUTES_PER_DAY = 1440;
    private const MINUTES_PER_QUARTER = 15;
    private const QUARTERS_PER_DAY = self::MINUTES_PER_DAY / self::MINUTES_PER_QUARTER;

    public function __construct(private readonly int $minimumOccurrences)
    {
    }

    /**
     * @param array $subEvents
     *   The subEvent list of an event or place, as an associative array. Expected to be poly-filled
     *   from openingHours and widened with childcare already, so the resolved hours are the hours a
     *   visitor can actually attend.
     * @return array<string, list<array{gte: int, lt: int}>>
     *   Per day of week, the local times the offer recurs on, as HHMM integers. A day of week
     *   without recurring hours is absent, so it stays distinguishable from a day of week that
     *   recurs without ever settling on an hour.
     */
    public function resolve(array $subEvents, DateTimeZone $timezone): array
    {
        $pieces = $this->cutIntoCalendarDays($subEvents, $timezone);
        $occupied = $this->markOccupiedQuarters($pieces);
        $counts = $this->countDatesPerQuarter($occupied);

        $ranges = [];
        foreach (DayOfWeek::cases() as $dayOfWeek) {
            $rangesForDay = $this->joinIntoRanges($counts[$dayOfWeek->value] ?? []);

            if ($rangesForDay !== []) {
                $ranges[$dayOfWeek->value] = $rangesForDay;
            }
        }

        return $ranges;
    }

    /**
     * @return list<array{string, string, int, int}>
     *   The day of week, the date, and the minutes since midnight the piece covers on it.
     */
    private function cutIntoCalendarDays(array $subEvents, DateTimeZone $timezone): array
    {
        $pieces = [];

        foreach ($subEvents as $subEvent) {
            $startDate = $this->parseDate($subEvent['startDate'] ?? null, $timezone);
            $endDate = $this->parseDate($subEvent['endDate'] ?? null, $timezone);

            // Missing and inverted ranges are logged already when building dateRange.
            if ($startDate === null || $endDate === null || $endDate < $startDate) {
                continue;
            }

            for ($day = $startDate->setTime(0, 0); $day <= $endDate; $day = $day->modify('+1 day')) {
                $dayAfter = $day->modify('+1 day');

                $from = $startDate > $day ? $startDate : $day;
                $until = $endDate < $dayAfter ? $endDate : $dayAfter;

                if ($until <= $from) {
                    continue;
                }

                $pieces[] = [
                    DayOfWeek::fromDate($day)->value,
                    $day->format('Y-m-d'),
                    $this->minutesSinceMidnight($from),
                    $until >= $dayAfter ? self::MINUTES_PER_DAY : $this->minutesSinceMidnight($until),
                ];
            }
        }

        return $pieces;
    }

    /**
     * @param list<array{string, string, int, int}> $pieces
     * @return array<string, array<string, array<int, true>>>
     */
    private function markOccupiedQuarters(array $pieces): array
    {
        $occupied = [];

        foreach ($pieces as [$dayOfWeek, $date, $from, $until]) {
            $firstQuarter = intdiv($from, self::MINUTES_PER_QUARTER);
            $quarterAfterLast = (int) ceil($until / self::MINUTES_PER_QUARTER);

            for ($quarter = $firstQuarter; $quarter < $quarterAfterLast; $quarter++) {
                $occupied[$dayOfWeek][$date][$quarter] = true;
            }
        }

        return $occupied;
    }

    /**
     * @param array<string, array<string, array<int, true>>> $occupied
     * @return array<string, array<int, int>>
     */
    private function countDatesPerQuarter(array $occupied): array
    {
        $counts = [];

        foreach ($occupied as $dayOfWeek => $quartersPerDate) {
            foreach ($quartersPerDate as $quarters) {
                foreach (array_keys($quarters) as $quarter) {
                    $counts[$dayOfWeek][$quarter] = ($counts[$dayOfWeek][$quarter] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }

    /**
     * @param array<int, int> $countsPerQuarter
     * @return list<array{gte: int, lt: int}>
     */
    private function joinIntoRanges(array $countsPerQuarter): array
    {
        $ranges = [];
        $runStart = null;

        for ($quarter = 0; $quarter <= self::QUARTERS_PER_DAY; $quarter++) {
            if (($countsPerQuarter[$quarter] ?? 0) >= $this->minimumOccurrences) {
                $runStart ??= $quarter;
                continue;
            }

            if ($runStart !== null) {
                $ranges[] = [
                    'gte' => $this->toLocalTime($runStart * self::MINUTES_PER_QUARTER),
                    'lt' => $this->toLocalTime($quarter * self::MINUTES_PER_QUARTER),
                ];
                $runStart = null;
            }
        }

        return $ranges;
    }

    /**
     * Reads the clock rather than subtracting the timestamp of midnight, because on the day daylight
     * saving starts 14:00 is only 13 hours after midnight.
     */
    private function minutesSinceMidnight(DateTimeImmutable $moment): int
    {
        return ((int) $moment->format('G')) * 60 + (int) $moment->format('i');
    }

    private function toLocalTime(int $minutes): int
    {
        return intdiv($minutes, 60) * 100 + $minutes % 60;
    }

    private function parseDate(?string $date, DateTimeZone $timezone): ?DateTimeImmutable
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
}

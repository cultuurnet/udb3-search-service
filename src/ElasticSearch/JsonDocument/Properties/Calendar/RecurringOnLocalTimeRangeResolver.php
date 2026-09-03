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
 * 2. Merge what overlaps on the same date. 10:00-12:00 and 11:00-13:00 on one Wednesday is one
 *    Wednesday of 10:00-13:00, not two.
 * 3. Count how many dates cover each minute. Only the minutes where a piece begins or ends can
 *    change that count, so those are the only ones visited.
 * 4. Keep the minutes reaching the minimum and join them into ranges. Half open, so an activity
 *    ending at 12:00 does not answer a search starting at 12:00.
 */
final class RecurringOnLocalTimeRangeResolver
{
    private const MINUTES_PER_DAY = 1440;

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
        $intervals = $this->mergeOverlappingPerDate($pieces);
        $counts = $this->countDatesPerMinute($intervals);

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
     * @return array<string, array<string, list<array{int, int}>>>
     */
    private function mergeOverlappingPerDate(array $pieces): array
    {
        $perDate = [];
        foreach ($pieces as [$dayOfWeek, $date, $from, $until]) {
            $perDate[$dayOfWeek][$date][] = [$from, $until];
        }

        foreach ($perDate as $dayOfWeek => $dates) {
            foreach ($dates as $date => $intervals) {
                $perDate[$dayOfWeek][$date] = $this->withoutOverlap($intervals);
            }
        }

        return $perDate;
    }

    /**
     * @param array<string, array<string, list<array{int, int}>>> $intervals
     * @return array<string, array<int, int>>
     *   Per minute where the number of covering dates changes, by how much it changes.
     */
    private function countDatesPerMinute(array $intervals): array
    {
        $counts = [];

        foreach ($intervals as $dayOfWeek => $intervalsPerDate) {
            foreach ($intervalsPerDate as $onDate) {
                foreach ($onDate as [$from, $until]) {
                    $counts[$dayOfWeek][$from] = ($counts[$dayOfWeek][$from] ?? 0) + 1;
                    $counts[$dayOfWeek][$until] = ($counts[$dayOfWeek][$until] ?? 0) - 1;
                }
            }

            ksort($counts[$dayOfWeek]);
        }

        return $counts;
    }

    /**
     * @param array<int, int> $countsPerMinute
     * @return list<array{gte: int, lt: int}>
     */
    private function joinIntoRanges(array $countsPerMinute): array
    {
        $ranges = [];
        $runStart = null;
        $covering = 0;

        foreach ($countsPerMinute as $minute => $change) {
            $covering += $change;

            if ($covering >= $this->minimumOccurrences) {
                $runStart ??= $minute;
                continue;
            }

            if ($runStart !== null) {
                $ranges[] = [
                    'gte' => $this->toLocalTime($runStart),
                    'lt' => $this->toLocalTime($minute),
                ];
                $runStart = null;
            }
        }

        return $ranges;
    }

    /**
     * @param list<array{int, int}> $intervals
     * @return list<array{int, int}>
     */
    private function withoutOverlap(array $intervals): array
    {
        sort($intervals);

        $merged = [];
        foreach ($intervals as [$from, $until]) {
            $last = count($merged) - 1;

            if ($last >= 0 && $from <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $until);
                continue;
            }

            $merged[] = [$from, $until];
        }

        return $merged;
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

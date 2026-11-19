<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use DateTimeImmutable;

trait MediumPermanentWeekScheme
{
    /**
     * @param array<int, string> $weekDaysOpen
     */
    public function getWeekScheme(array $weekDaysOpen, DateFormatter $formatter, bool $abbreviated = true): array
    {
        $formatDayName = (static fn (string $dayName): string => $abbreviated
            ? $formatter->formatAsAbbreviatedDayOfWeek(new DateTimeImmutable($dayName))
            : $formatter->formatAsDayOfWeek(new DateTimeImmutable($dayName)));

        // Do no assume people will order the days consecutive
        ksort($weekDaysOpen);
        $translatedDayNamesWithOpeningHours = [];
        $dayPeriod = '';
        $startNewPeriod = true;

        foreach ($weekDaysOpen as $weekDayNumber => $dayName) {
            // We start a new period, but the following day is closed
            if ($startNewPeriod && !array_key_exists($weekDayNumber + 1, $weekDaysOpen)) {
                $translatedDayNamesWithOpeningHours[] = $formatDayName($dayName);
            }
            // Start a new period and the following day is open
            if ($startNewPeriod && array_key_exists($weekDayNumber + 1, $weekDaysOpen)) {
                $dayPeriod = $formatDayName($dayName);
                $startNewPeriod = false;
            }
            // The previous day was open but the following day isn't
            if (!$startNewPeriod && !array_key_exists($weekDayNumber + 1, $weekDaysOpen)) {
                $dayPeriod .= ' - ' . $formatDayName($dayName);
                $translatedDayNamesWithOpeningHours[] = $dayPeriod;
                $startNewPeriod = true;
                $dayPeriod = '';
            }
            // Do nothing if both the previous & following day are open
        }

        return $translatedDayNamesWithOpeningHours;
    }

    /**
     * @param array<int, string> $weekDaysOpen
     */
    public function isFirstPeriodASingleDay(array $weekDaysOpen): bool
    {
        $firstDay = array_key_first($weekDaysOpen);

        return $firstDay === null || !array_key_exists($firstDay + 1, $weekDaysOpen);
    }
}

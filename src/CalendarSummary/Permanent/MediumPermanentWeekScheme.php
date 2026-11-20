<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use DateTimeImmutable;

trait MediumPermanentWeekScheme
{
    public function getWeekScheme(array $weekDaysOpen, DateFormatter $formatter, bool $abbreviated = true): array
    {
        $formatDayName = static function (string $dayName) use ($formatter, $abbreviated): string {
            return $abbreviated
                ? $formatter->formatAsAbbreviatedDayOfWeek(new DateTimeImmutable($dayName))
                : $formatter->formatAsDayOfWeek(new DateTimeImmutable($dayName));
        };

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

    public function isFirstPeriodASingleDay(array $weekDaysOpen): bool
    {
        return !array_key_exists(array_key_first($weekDaysOpen) + 1, $weekDaysOpen);
    }
}

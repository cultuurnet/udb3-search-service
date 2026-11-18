<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

final class OpeningHourFormatter
{
    /**
     * Removes the leading 0 from the hour.
     * For example 00:15 -> 0:15, 01:00 -> 1:00, etc.
     *
     */
    public static function format(string $openingHour): string
    {
        $firstCharacter = $openingHour[0];
        if ($firstCharacter === '0') {
            $openingHour = substr($openingHour, 1);
        }
        return $openingHour;
    }
}

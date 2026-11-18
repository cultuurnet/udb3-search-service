<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Carbon\CarbonImmutable;

final class CalendarSummaryTester
{
    public static function setTestNow(int $year = 0, int $month = 1, int $day = 1, int $hour = 0, int $minute = 0, int $second = 0, $tz = null)
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create($year, $month, $day, $hour, $minute, $second, $tz));
    }
}

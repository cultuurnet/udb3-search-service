<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Carbon\CarbonImmutable;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Period;
use DateInterval;
use DateTimeImmutable;

final class DateComparison
{
    public static function onSameDay(DateTimeImmutable $date1, DateTimeImmutable $date2): bool
    {
        return CarbonImmutable::instance($date1)->isSameDay(
            CarbonImmutable::instance($date2)
        );
    }

    public static function isThisEvening(DateTimeImmutable $date): bool
    {
        return self::onSameDay($date, new CarbonImmutable()) && (int)$date->format('G') >= 18;
    }

    public static function isToday(DateTimeImmutable $date): bool
    {
        return self::onSameDay($date, new CarbonImmutable());
    }

    public static function isTomorrow(DateTimeImmutable $date): bool
    {
        return self::onSameDay($date, (new CarbonImmutable())->add(new DateInterval('P1D')));
    }

    public static function isInCurrentWeek(DateTimeImmutable $date): bool
    {
        return CarbonImmutable::instance($date)->isCurrentWeek();
    }

    public static function isUpcomingDayInCurrentWeek(DateTimeImmutable $date): bool
    {
        return self::isInTheFuture($date) && self::isInCurrentWeek($date);
    }

    public static function isCurrentYear(DateTimeImmutable $date): bool
    {
        return CarbonImmutable::instance($date)->isCurrentYear();
    }

    public static function isInTheFuture(DateTimeImmutable $date): bool
    {
        $now = new CarbonImmutable();
        return $date > $now;
    }

    /**
     * Unlike isInTheFuture() this ignores the time of day, so a date-only value
     * for today is not considered to be in the past.
     */
    public static function isPastDay(DateTimeImmutable $date): bool
    {
        return CarbonImmutable::instance($date)->endOfDay() < new CarbonImmutable();
    }

    /**
     * @template T of Period
     * @param T[] $periods
     * @return T[]
     */
    public static function withoutPastPeriods(array $periods): array
    {
        return array_filter($periods, static function (Period $period): bool {
            return !self::isPastDay($period->getEndDate());
        });
    }
}

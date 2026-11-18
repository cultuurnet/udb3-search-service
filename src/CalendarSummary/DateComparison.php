<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Period;
use DateTimeInterface;

final class DateComparison
{
    public static function onSameDay(DateTimeInterface $date1, DateTimeInterface $date2): bool
    {
        return Chronos::instance($date1)->isSameDay($date2);
    }

    public static function isThisEvening(DateTimeInterface $date): bool
    {
        return self::onSameDay($date, Chronos::now()) && (int)$date->format('G') >= 18;
    }

    public static function isToday(DateTimeInterface $date): bool
    {
        return self::onSameDay($date, Chronos::now());
    }

    public static function isTomorrow(DateTimeInterface $date): bool
    {
        return self::onSameDay($date, Chronos::now()->addDays(1));
    }

    public static function isInCurrentWeek(DateTimeInterface $date): bool
    {
        return Chronos::instance($date)->isThisWeek();
    }

    public static function isUpcomingDayInCurrentWeek(DateTimeInterface $date): bool
    {
        return self::isInTheFuture($date) && self::isInCurrentWeek($date);
    }

    public static function isCurrentYear(DateTimeInterface $date): bool
    {
        return Chronos::instance($date)->isThisYear();
    }

    public static function isInTheFuture(DateTimeInterface $date): bool
    {
        $now = Chronos::now();
        return $date > $now;
    }

    /**
     * Unlike isInTheFuture() this ignores the time of day, so a date-only value
     * for today is not considered to be in the past.
     */
    public static function isPastDay(DateTimeInterface $date): bool
    {
        return Chronos::instance($date)->endOfDay(true) < Chronos::now();
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

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;

final class DateComparisonTest extends TestCase
{
    protected function setUp(): void
    {
        Chronos::setTestNow(new Chronos('2021-05-04'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    public function testAreTwoDatesOnTheSameDay(): void
    {
        $this->assertTrue(
            DateComparison::onSameDay(
                new Chronos('2029-09-09 00:30'),
                new Chronos('2029-09-09 23:30')
            )
        );
        $this->assertFalse(
            DateComparison::onSameDay(
                new Chronos('2029-09-08 23:59'),
                new Chronos('2029-09-09 00:01')
            )
        );
    }

    public function testIsItTonight(): void
    {
        $this->assertTrue(
            DateComparison::isThisEvening(
                new Chronos('2021-05-04 18:00')
            )
        );
        $this->assertFalse(
            DateComparison::isThisEvening(
                new Chronos('2021-05-04 17:59')
            )
        );
    }

    public function testIsItToday(): void
    {
        $this->assertTrue(
            DateComparison::isToday(
                new Chronos('2021-05-04 18:00')
            )
        );
        $this->assertFalse(
            DateComparison::isToday(
                new Chronos('2021-05-08 17:59')
            )
        );
    }

    public function testIsItTomorrow(): void
    {
        $this->assertTrue(
            DateComparison::isTomorrow(
                new Chronos('2021-05-05 18:00')
            )
        );
        $this->assertFalse(
            DateComparison::isTomorrow(
                new Chronos('2021-05-04 17:59')
            )
        );
    }

    public function testIsItTheCurrentWeek(): void
    {
        $this->assertTrue(
            DateComparison::isUpcomingDayInCurrentWeek(
                new Chronos('2021-05-07 18:00')
            )
        );
        $this->assertFalse(
            DateComparison::isUpcomingDayInCurrentWeek(
                new Chronos('2021-05-03 17:59')
            )
        );
    }

    public function testIsItTheCurrentYear(): void
    {
        $this->assertTrue(
            DateComparison::isCurrentYear(
                new Chronos('2021-12-31 18:00')
            )
        );
        $this->assertFalse(
            DateComparison::isCurrentYear(
                new Chronos('2020-02-03 17:59')
            )
        );
    }

    public function testIsItTheFuture(): void
    {
        $this->assertTrue(
            DateComparison::isInTheFuture(
                new Chronos('2021-12-31 18:00')
            )
        );
        $this->assertFalse(
            DateComparison::isInTheFuture(
                new Chronos('2021-02-03 17:59')
            )
        );
    }
}

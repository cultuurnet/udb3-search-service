<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class DateComparisonTest extends TestCase
{
    protected function setUp(): void
    {
        CalendarSummaryTester::setTestNow(2021, 5, 4);
    }

    public function testAreTwoDatesOnTheSameDay(): void
    {
        $this->assertTrue(
            DateComparison::onSameDay(
                CarbonImmutable::create(2029, 9, 9)->setTime(0, 30),
                CarbonImmutable::create(2029, 9, 9)->setTime(23, 30)
            )
        );
        $this->assertFalse(
            DateComparison::onSameDay(
                CarbonImmutable::create(2029, 9, 8)->setTime(23, 59),
                CarbonImmutable::create(2029, 9, 9)->setTime(0, 01)
            )
        );
    }

    public function testIsItTonight(): void
    {
        $this->assertTrue(
            DateComparison::isThisEvening(
                CarbonImmutable::create(2021, 5, 4)->setTime(18, 0)
            )
        );
        $this->assertFalse(
            DateComparison::isThisEvening(
                CarbonImmutable::create(2021, 5, 4)->setTime(17, 59)
            )
        );
    }

    public function testIsItToday(): void
    {
        $this->assertTrue(
            DateComparison::isToday(
                CarbonImmutable::create(2021, 5, 4)->setTime(18, 0)
            )
        );
        $this->assertFalse(
            DateComparison::isToday(
                CarbonImmutable::create(2021, 5, 8)->setTime(17, 59)
            )
        );
    }

    public function testIsItTomorrow(): void
    {
        $this->assertTrue(
            DateComparison::isTomorrow(
                CarbonImmutable::create(2021, 5, 5)->setTime(18, 0)
            )
        );
        $this->assertFalse(
            DateComparison::isTomorrow(
                CarbonImmutable::create(2021, 5, 4)->setTime(17, 59)
            )
        );
    }

    public function testIsItTheCurrentWeek(): void
    {
        $this->assertTrue(
            DateComparison::isUpcomingDayInCurrentWeek(
                CarbonImmutable::create(2021, 5, 7)->setTime(18, 0)
            )
        );
        $this->assertFalse(
            DateComparison::isUpcomingDayInCurrentWeek(
                CarbonImmutable::create(2021, 5, 3)->setTime(17, 59)
            )
        );
    }

    public function testIsItTheCurrentYear(): void
    {
        $this->assertTrue(
            DateComparison::isCurrentYear(
                CarbonImmutable::create(2021, 12, 31)->setTime(18, 0)
            )
        );
        $this->assertFalse(
            DateComparison::isCurrentYear(
                CarbonImmutable::create(2020, 2, 3)->setTime(17, 59)
            )
        );
    }

    public function testIsItTheFuture(): void
    {
        $this->assertTrue(
            DateComparison::isInTheFuture(
                CarbonImmutable::create(2021, 12, 31)->setTime(18, 0)
            )
        );
        $this->assertFalse(
            DateComparison::isInTheFuture(
                CarbonImmutable::create(2021, 2, 3)->setTime(17, 59)
            )
        );
    }
}

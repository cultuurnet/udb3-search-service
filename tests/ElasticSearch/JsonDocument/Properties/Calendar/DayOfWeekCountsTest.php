<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use PHPUnit\Framework\TestCase;

final class DayOfWeekCountsTest extends TestCase
{
    /**
     * @test
     */
    public function it_starts_every_weekday_at_zero(): void
    {
        $counts = new DayOfWeekCounts();

        foreach (DayOfWeek::cases() as $day) {
            $this->assertSame(0, $counts->forDay($day));
        }
    }

    /**
     * @test
     */
    public function it_increments_only_the_given_weekday(): void
    {
        $counts = new DayOfWeekCounts();
        $counts->increment(DayOfWeek::Wednesday);
        $counts->increment(DayOfWeek::Wednesday);

        $this->assertSame(2, $counts->forDay(DayOfWeek::Wednesday));
        $this->assertSame(0, $counts->forDay(DayOfWeek::Tuesday));
    }

    /**
     * @test
     */
    public function it_reports_no_weekdays_when_none_reach_the_threshold(): void
    {
        $counts = new DayOfWeekCounts();
        $this->incrementTimes($counts, DayOfWeek::Monday, 3);

        $this->assertSame([], $counts->weekdaysReaching(4));
    }

    /**
     * @test
     */
    public function it_includes_a_weekday_that_exactly_reaches_the_threshold(): void
    {
        $counts = new DayOfWeekCounts();
        $this->incrementTimes($counts, DayOfWeek::Monday, 4);

        $this->assertSame([DayOfWeek::Monday], $counts->weekdaysReaching(4));
    }

    /**
     * @test
     */
    public function it_returns_reaching_weekdays_in_canonical_week_order(): void
    {
        $counts = new DayOfWeekCounts();
        // Increment out of week order to prove the result is ordered by the enum, not by insertion.
        $this->incrementTimes($counts, DayOfWeek::Friday, 4);
        $this->incrementTimes($counts, DayOfWeek::Monday, 4);
        $this->incrementTimes($counts, DayOfWeek::Wednesday, 4);
        // Tuesday stays below the threshold and must be excluded.
        $this->incrementTimes($counts, DayOfWeek::Tuesday, 3);

        $this->assertSame(
            [DayOfWeek::Monday, DayOfWeek::Wednesday, DayOfWeek::Friday],
            $counts->weekdaysReaching(4)
        );
    }

    private function incrementTimes(DayOfWeekCounts $counts, DayOfWeek $day, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $counts->increment($day);
        }
    }
}

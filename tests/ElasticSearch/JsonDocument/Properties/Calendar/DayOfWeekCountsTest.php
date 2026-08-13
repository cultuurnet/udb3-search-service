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
    public function it_starts_every_day_of_week_at_zero(): void
    {
        $counts = new DayOfWeekCounts();

        foreach (DayOfWeek::cases() as $day) {
            $this->assertSame(0, $counts->forDay($day));
        }
    }

    /**
     * @test
     */
    public function it_increments_only_the_given_day_of_week(): void
    {
        $counts = (new DayOfWeekCounts())
            ->withIncremented(DayOfWeek::Wednesday)
            ->withIncremented(DayOfWeek::Wednesday);

        $this->assertSame(2, $counts->forDay(DayOfWeek::Wednesday));
        $this->assertSame(0, $counts->forDay(DayOfWeek::Tuesday));
    }

    /**
     * @test
     */
    public function it_leaves_the_original_counts_untouched_when_incrementing(): void
    {
        $original = new DayOfWeekCounts();
        $incremented = $original->withIncremented(DayOfWeek::Wednesday);

        $this->assertSame(0, $original->forDay(DayOfWeek::Wednesday));
        $this->assertSame(1, $incremented->forDay(DayOfWeek::Wednesday));
    }

    /**
     * @test
     */
    public function it_reports_no_days_of_week_when_none_reach_the_threshold(): void
    {
        $counts = $this->incrementTimes(new DayOfWeekCounts(), DayOfWeek::Monday, 3);

        $this->assertSame([], $counts->daysOfWeekReaching(4));
    }

    /**
     * @test
     */
    public function it_includes_a_day_of_week_that_exactly_reaches_the_threshold(): void
    {
        $counts = $this->incrementTimes(new DayOfWeekCounts(), DayOfWeek::Monday, 4);

        $this->assertSame([DayOfWeek::Monday], $counts->daysOfWeekReaching(4));
    }

    /**
     * @test
     */
    public function it_returns_reaching_days_of_week_in_canonical_week_order(): void
    {
        $counts = new DayOfWeekCounts();
        // Increment out of week order to prove the result is ordered by the enum, not by insertion.
        $counts = $this->incrementTimes($counts, DayOfWeek::Friday, 4);
        $counts = $this->incrementTimes($counts, DayOfWeek::Monday, 4);
        $counts = $this->incrementTimes($counts, DayOfWeek::Wednesday, 4);
        // Tuesday stays below the threshold and must be excluded.
        $counts = $this->incrementTimes($counts, DayOfWeek::Tuesday, 3);

        $this->assertSame(
            [DayOfWeek::Monday, DayOfWeek::Wednesday, DayOfWeek::Friday],
            $counts->daysOfWeekReaching(4)
        );
    }

    private function incrementTimes(DayOfWeekCounts $counts, DayOfWeek $day, int $times): DayOfWeekCounts
    {
        for ($i = 0; $i < $times; $i++) {
            $counts = $counts->withIncremented($day);
        }

        return $counts;
    }
}

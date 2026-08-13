<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use CultuurNet\UDB3\Search\Offer\DayOfWeek;

final class DayOfWeekCounts
{
    /**
     * @var array<string, int>
     */
    private array $counts;

    public function __construct()
    {
        $this->counts = [];
        foreach (DayOfWeek::cases() as $day) {
            $this->counts[$day->value] = 0;
        }
    }

    public function withIncremented(DayOfWeek $day): self
    {
        $incremented = clone $this;
        $incremented->counts[$day->value]++;

        return $incremented;
    }

    public function forDay(DayOfWeek $day): int
    {
        return $this->counts[$day->value];
    }

    /**
     * @return list<DayOfWeek>
     *   The days of week, in canonical week order, reached on at least $threshold days.
     */
    public function daysOfWeekReaching(int $threshold): array
    {
        return array_values(array_filter(
            DayOfWeek::cases(),
            fn (DayOfWeek $day): bool => $this->counts[$day->value] >= $threshold
        ));
    }
}

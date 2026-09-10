<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use DateTimeInterface;

final class EffectiveOpeningHours
{
    /**
     * @param list<array{date: DateTimeInterface, opens: string, closes: string, childcare: array{start?: string, end?: string}|null}> $slots
     */
    public function __construct(
        private readonly array $slots,
        private readonly DayOfWeekCounts $dayCounts
    ) {
    }

    public static function empty(): self
    {
        return new self([], new DayOfWeekCounts());
    }

    /**
     * @return list<array{date: DateTimeInterface, opens: string, closes: string, childcare: array{start?: string, end?: string}|null}>
     */
    public function slots(): array
    {
        return $this->slots;
    }

    public function dayCounts(): DayOfWeekCounts
    {
        return $this->dayCounts;
    }
}

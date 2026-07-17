<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use DateTimeInterface;

final class EffectiveOpeningHours
{
    /**
     * @param list<array{date: DateTimeInterface, opens: string, closes: string}> $slots
     * @param array{monday: int, tuesday: int, wednesday: int, thursday: int, friday: int, saturday: int, sunday: int} $dayCounts
     */
    public function __construct(
        private readonly array $slots,
        private readonly array $dayCounts
    ) {
    }

    public static function empty(): self
    {
        return new self([], [
            'monday' => 0,
            'tuesday' => 0,
            'wednesday' => 0,
            'thursday' => 0,
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0,
        ]);
    }

    /**
     * @return list<array{date: DateTimeInterface, opens: string, closes: string}>
     */
    public function slots(): array
    {
        return $this->slots;
    }

    /**
     * @return array{monday: int, tuesday: int, wednesday: int, thursday: int, friday: int, saturday: int, sunday: int}
     */
    public function dayCounts(): array
    {
        return $this->dayCounts;
    }
}

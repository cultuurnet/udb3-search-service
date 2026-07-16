<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use DateTimeInterface;

final class EffectiveOpeningHours
{
    /**
     * @param list<array{date: DateTimeInterface, opens: string, closes: string}> $slots
     */
    public function __construct(private readonly array $slots)
    {
    }

    /**
     * @return list<array{date: DateTimeInterface, opens: string, closes: string}>
     */
    public function slots(): array
    {
        return $this->slots;
    }
}

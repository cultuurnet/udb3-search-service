<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;

final class BirthdateRange
{
    private DateTimeImmutable $from;

    private DateTimeImmutable $to;

    public function __construct(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable
    {
        return $this->to;
    }
}

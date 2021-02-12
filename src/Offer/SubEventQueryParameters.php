<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;

final class SubEventQueryParameters
{
    /**
     * @var DateTimeImmutable|null
     */
    private $dateFrom;

    /**
     * @var DateTimeImmutable|null
     */
    private $dateTo;

    /**
     * @var Status[]
     */
    private $statuses = [];

    public function getDateFrom(): ?DateTimeImmutable
    {
        return $this->dateFrom;
    }

    public function withDateFrom(?DateTimeImmutable $dateFrom): SubEventQueryParameters
    {
        $c = clone $this;
        $c->dateFrom = $dateFrom;
        return $c;
    }

    public function getDateTo(): ?DateTimeImmutable
    {
        return $this->dateTo;
    }

    public function withDateTo(?DateTimeImmutable $dateTo): SubEventQueryParameters
    {
        $c = clone $this;
        $c->dateTo = $dateTo;
        return $c;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function withStatuses(array $statuses): SubEventQueryParameters
    {
        $c = clone $this;
        $c->statuses = $statuses;
        return $c;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;

final class SubEventQueryParameters
{
    private ?DateTimeImmutable $dateFrom = null;

    private ?DateTimeImmutable $dateTo = null;

    private ?int $localTimeFrom = null;

    private ?int $localTimeTo = null;

    /**
     * @var Status[]
     */
    private array $statuses = [];

    private ?string $bookingAvailability = null;

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

    public function getLocalTimeFrom(): ?int
    {
        return $this->localTimeFrom;
    }

    public function withLocalTimeFrom(?int $localTimeFrom): SubEventQueryParameters
    {
        $c = clone $this;
        $c->localTimeFrom = $localTimeFrom;
        return $c;
    }

    public function getLocalTimeTo(): ?int
    {
        return $this->localTimeTo;
    }

    public function withLocalTimeTo(?int $localTimeTo): SubEventQueryParameters
    {
        $c = clone $this;
        $c->localTimeTo = $localTimeTo;
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

    public function getBookingAvailability(): ?string
    {
        return $this->bookingAvailability;
    }

    public function withBookingAvailability(?string $bookingAvailability): SubEventQueryParameters
    {
        $c = clone $this;
        $c->bookingAvailability = $bookingAvailability;
        return $c;
    }
}

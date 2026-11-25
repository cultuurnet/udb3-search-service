<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use JsonSerializable;

final class PlaceEventCounts implements JsonSerializable
{
    /**
     * @var PlaceEventCount[]
     */
    public readonly array $places;

    public function __construct(
        public readonly int $totalEvents,
        PlaceEventCount ...$places
    ) {
        $this->places = $places;
    }

    public function jsonSerialize(): array
    {
        return [
            'totalEvents' => $this->totalEvents,
            'places' => $this->places,
        ];
    }
}

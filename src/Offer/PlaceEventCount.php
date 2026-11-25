<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use JsonSerializable;

final class PlaceEventCount implements JsonSerializable
{
    /**
     * @param array<string, string> $name
     */
    public function __construct(
        public readonly string $iri,
        public readonly array $name,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $eventCount
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            '@id' => $this->iri,
            // An empty PHP array would serialize as [] instead of the {} a language map is.
            'name' => (object) $this->name,
            'geo' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'eventCount' => $this->eventCount,
        ];
    }
}

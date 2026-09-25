<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;

final class GeoDistanceQuery implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $distance,
        private readonly Coordinates $location
    ) {
    }

    public function toArray(): array
    {
        return [
            'geo_distance' => [
                'distance' => $this->distance,
                $this->field => (object) [
                    'lat' => $this->location->getLatitude()->toDouble(),
                    'lon' => $this->location->getLongitude()->toDouble(),
                ],
            ],
        ];
    }
}

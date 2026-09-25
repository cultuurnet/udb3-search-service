<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\SortOrder;

final class GeoDistanceSort implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly Coordinates $location,
        private readonly SortOrder $order
    ) {
    }

    public function toArray(): array
    {
        return [
            '_geo_distance' => [
                'order' => $this->order->value,
                $this->field => [
                    'lat' => $this->location->getLatitude()->toDouble(),
                    'lon' => $this->location->getLongitude()->toDouble(),
                ],
                'unit' => 'km',
                'distance_type' => 'plane',
            ],
        ];
    }
}

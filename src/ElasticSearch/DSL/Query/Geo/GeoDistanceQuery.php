<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistance;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class GeoDistanceQuery implements BuilderInterface, OngrBuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly ElasticSearchDistance $distance,
        private readonly Coordinates $location
    ) {
    }

    public function toArray(): array
    {
        return [
            'geo_distance' => [
                'distance' => $this->distance->toString(),
                $this->field => [
                    'lat' => $this->location->getLatitude()->toDouble(),
                    'lon' => $this->location->getLongitude()->toDouble(),
                ],
            ],
        ];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'geo_distance';
    }
}

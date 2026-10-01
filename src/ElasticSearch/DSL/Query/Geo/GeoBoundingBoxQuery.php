<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class GeoBoundingBoxQuery implements BuilderInterface, OngrBuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly Coordinates $topLeft,
        private readonly Coordinates $bottomRight
    ) {
    }

    public function toArray(): array
    {
        return [
            'geo_bounding_box' => [
                $this->field => [
                    'top_left' => [
                        'lat' => $this->topLeft->getLatitude()->toDouble(),
                        'lon' => $this->topLeft->getLongitude()->toDouble(),
                    ],
                    'bottom_right' => [
                        'lat' => $this->bottomRight->getLatitude()->toDouble(),
                        'lon' => $this->bottomRight->getLongitude()->toDouble(),
                    ],
                ],
            ],
        ];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'geo_bounding_box';
    }
}

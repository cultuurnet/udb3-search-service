<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class GeoBoundingBoxQuery implements BuilderInterface
{
    /**
     * @param array $bounds [$topLeft, $bottomRight] — each an associative array with 'lat' and 'lon' keys.
     */
    public function __construct(
        private readonly string $field,
        private readonly array $bounds
    ) {
    }

    public function toArray(): array
    {
        return [
            'geo_bounding_box' => [
                $this->field => [
                    'top_left' => $this->bounds[0],
                    'bottom_right' => $this->bounds[1],
                ],
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class GeoBoundingBoxQuery implements BuilderInterface
{
    /**
     * @param array $bounds 2 values: [$topLeft, $bottomRight] or ['top_left' => ..., 'bottom_right' => ...]
     *                      4 values: [$top, $left, $bottom, $right] or ['top' => ..., 'left' => ..., 'bottom' => ..., 'right' => ...]
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
                $this->field => $this->points(),
            ],
        ];
    }

    private function points(): array
    {
        if (count($this->bounds) === 2) {
            return [
                'top_left' => $this->bounds[0] ?? $this->bounds['top_left'],
                'bottom_right' => $this->bounds[1] ?? $this->bounds['bottom_right'],
            ];
        }

        if (count($this->bounds) === 4) {
            return [
                'top' => $this->bounds[0] ?? $this->bounds['top'],
                'left' => $this->bounds[1] ?? $this->bounds['left'],
                'bottom' => $this->bounds[2] ?? $this->bounds['bottom'],
                'right' => $this->bounds[3] ?? $this->bounds['right'],
            ];
        }

        throw new \LogicException('Geo Bounding Box query must have 2 or 4 geo points set.');
    }
}

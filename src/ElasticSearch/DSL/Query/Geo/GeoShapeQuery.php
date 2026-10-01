<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class GeoShapeQuery implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $id,
        private readonly string $index,
        private readonly string $path
    ) {
    }

    public function toArray(): array
    {
        return [
            'geo_shape' => [
                $this->field => [
                    'indexed_shape' => [
                        'id' => $this->id,
                        'index' => $this->index,
                        'path' => $this->path,
                    ],
                    'relation' => 'intersects',
                ],
            ],
        ];
    }
}

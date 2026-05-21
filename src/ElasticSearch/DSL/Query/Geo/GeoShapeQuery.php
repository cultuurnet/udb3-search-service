<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class GeoShapeQuery implements BuilderInterface
{
    private ?string $field = null;

    private ?string $id = null;

    private ?string $index = null;

    private ?string $path = null;

    public function addPreIndexedShape(
        string $field,
        string $id,
        string $index,
        string $path
    ): void {
        $this->field = $field;
        $this->id = $id;
        $this->index = $index;
        $this->path = $path;
    }

    public function addShape(
        string $field,
        string $type,
        array $coordinates,
        string $relation = 'intersects'
    ): void {
        throw new \RuntimeException('addShape is not yet implemented in the custom DSL library.');
    }

    public function toArray(): array
    {
        if ($this->field === null) {
            throw new \LogicException('addPreIndexedShape() must be called before toArray().');
        }

        return [
            'geo_shape' => [
                $this->field => [
                    'indexed_shape' => [
                        'id' => $this->id,
                        'index' => $this->index,
                        'path' => $this->path,
                    ],
                ],
            ],
        ];
    }
}

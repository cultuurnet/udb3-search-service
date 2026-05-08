<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class GeoShapeQuery implements BuilderInterface
{
    private string $field = '';

    private string $id = '';

    private string $index = '';

    private string $path = '';

    /**
     * The $type parameter is accepted for API compatibility but is not emitted —
     * ES8 removed the type field from indexed shape queries.
     */
    public function addPreIndexedShape(
        string $field,
        string $id,
        string $type,
        string $index,
        string $path
    ): void {
        $this->field = $field;
        $this->id = $id;
        $this->index = $index;
        $this->path = $path;
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
                ],
            ],
        ];
    }
}

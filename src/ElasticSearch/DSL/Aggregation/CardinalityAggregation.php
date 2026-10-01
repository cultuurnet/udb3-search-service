<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class CardinalityAggregation implements BuilderInterface
{
    public function __construct(
        private readonly string $field
    ) {
    }

    public function toArray(): array
    {
        return [
            'cardinality' => [
                'field' => $this->field,
            ],
        ];
    }
}

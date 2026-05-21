<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Metric;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\NamedBuilderInterface;

final class CardinalityAggregation implements NamedBuilderInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $field
    ) {
    }

    public function getName(): string
    {
        return $this->name;
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

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Metric;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class CardinalityAggregation implements BuilderInterface
{
    private string $field = '';

    public function __construct(
        private readonly string $name
    ) {
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function toArray(): array
    {
        return [
            $this->name => [
                'cardinality' => [
                    'field' => $this->field,
                ],
            ],
        ];
    }
}

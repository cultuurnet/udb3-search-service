<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class FilterAggregation implements BuilderInterface
{
    /**
     * @param array<string, BuilderInterface> $aggregations
     */
    public function __construct(
        private readonly BuilderInterface $filter,
        private readonly array $aggregations
    ) {
    }

    public function toArray(): array
    {
        return [
            'filter' => $this->filter->toArray(),
            'aggregations' => array_map(
                static fn (BuilderInterface $subAggregation): array => $subAggregation->toArray(),
                $this->aggregations
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class TermsAggregation implements BuilderInterface
{
    /**
     * @param string[] $exclude
     * @param array<string, BuilderInterface> $aggregations
     */
    public function __construct(
        private readonly string $field,
        private readonly ?int $size = null,
        private readonly array $exclude = [],
        private readonly array $aggregations = []
    ) {
    }

    public function toArray(): array
    {
        $terms = ['field' => $this->field];

        // Without a size, Elasticsearch applies its own default number of buckets.
        if ($this->size !== null) {
            $terms['size'] = $this->size;
        }

        if ($this->exclude !== []) {
            $terms['exclude'] = $this->exclude;
        }

        $aggregation = ['terms' => $terms];

        if ($this->aggregations !== []) {
            $aggregation['aggregations'] = array_map(
                static fn (BuilderInterface $subAggregation): array => $subAggregation->toArray(),
                $this->aggregations
            );
        }

        return $aggregation;
    }
}

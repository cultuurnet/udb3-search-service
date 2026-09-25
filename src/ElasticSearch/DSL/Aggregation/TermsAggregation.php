<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class TermsAggregation implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly ?int $size = null
    ) {
    }

    public function toArray(): array
    {
        $terms = ['field' => $this->field];

        // Without a size, Elasticsearch applies its own default number of buckets.
        if ($this->size !== null) {
            $terms['size'] = $this->size;
        }

        return ['terms' => $terms];
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class TopHitsAggregation implements BuilderInterface
{
    /**
     * @param string[] $source
     */
    public function __construct(
        private readonly int $size,
        private readonly array $source
    ) {
    }

    public function toArray(): array
    {
        return [
            'top_hits' => [
                'size' => $this->size,
                '_source' => $this->source,
            ],
        ];
    }
}

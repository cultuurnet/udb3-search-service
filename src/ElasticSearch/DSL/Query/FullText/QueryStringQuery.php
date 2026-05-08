<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class QueryStringQuery implements BuilderInterface
{
    public function __construct(
        private readonly string $query,
        private readonly array $parameters = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'query_string' => array_merge(
                ['query' => $this->query],
                $this->parameters
            ),
        ];
    }
}

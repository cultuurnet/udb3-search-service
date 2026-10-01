<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Joining;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class NestedQuery implements BuilderInterface
{
    public function __construct(
        private readonly string $path,
        private readonly BuilderInterface $query
    ) {
    }

    public function toArray(): array
    {
        return [
            'nested' => [
                'path' => $this->path,
                'query' => $this->query->toArray(),
            ],
        ];
    }
}

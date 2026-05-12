<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class MatchQuery implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $value
    ) {
    }

    public function toArray(): array
    {
        return ['match' => [$this->field => ['query' => $this->value]]];
    }
}

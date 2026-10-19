<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class TermQuery implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string|bool|int|float $value
    ) {
    }

    public function toArray(): array
    {
        return ['term' => [$this->field => $this->value]];
    }
}

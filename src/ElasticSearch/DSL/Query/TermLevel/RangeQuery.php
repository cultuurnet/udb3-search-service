<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class RangeQuery implements BuilderInterface
{
    public const GTE = 'gte';
    public const LTE = 'lte';

    public function __construct(
        private readonly string $field,
        private readonly array $parameters
    ) {
    }

    public function toArray(): array
    {
        return ['range' => [$this->field => $this->parameters]];
    }
}

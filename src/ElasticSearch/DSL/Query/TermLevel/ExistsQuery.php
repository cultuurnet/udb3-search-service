<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class ExistsQuery implements BuilderInterface
{
    public function __construct(private readonly string $field)
    {
    }

    public function toArray(): array
    {
        return ['exists' => ['field' => $this->field]];
    }
}

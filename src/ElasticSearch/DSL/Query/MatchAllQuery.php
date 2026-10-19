<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class MatchAllQuery implements BuilderInterface
{
    public function toArray(): array
    {
        return ['match_all' => new \stdClass()];
    }
}

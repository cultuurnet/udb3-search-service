<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class MatchAllQuery implements BuilderInterface, OngrBuilderInterface
{
    public function toArray(): array
    {
        return ['match_all' => new \stdClass()];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'match_all';
    }
}

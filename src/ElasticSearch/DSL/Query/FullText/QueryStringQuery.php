<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class QueryStringQuery implements BuilderInterface, OngrBuilderInterface
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

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'query_string';
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class QueryStringQuery implements BuilderInterface, OngrBuilderInterface
{
    /**
     * @param string[] $fields
     */
    public function __construct(
        private readonly string $query,
        private readonly array $fields = [],
        private readonly ?string $defaultOperator = null
    ) {
    }

    public function toArray(): array
    {
        $queryString = ['query' => $this->query];

        if (!empty($this->fields)) {
            $queryString['fields'] = $this->fields;
        }

        if ($this->defaultOperator !== null) {
            $queryString['default_operator'] = $this->defaultOperator;
        }

        return ['query_string' => $queryString];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'query_string';
    }
}

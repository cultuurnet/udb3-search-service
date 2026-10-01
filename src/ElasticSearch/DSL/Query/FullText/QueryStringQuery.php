<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class QueryStringQuery implements BuilderInterface
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
}

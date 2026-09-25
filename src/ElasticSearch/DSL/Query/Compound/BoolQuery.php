<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class BoolQuery implements BuilderInterface
{
    /** @var array<string, BuilderInterface[]> */
    private array $clauses = [];

    public function add(BuilderInterface $query, BoolClause $clause): void
    {
        $this->clauses[$clause->value][] = $query;
    }

    public function toArray(): array
    {
        // A lone must clause is emitted as the query itself, so a builder without any filters sends a plain
        // match_all instead of wrapping it in a bool.
        if (count($this->clauses) === 1 && count($this->clauses[BoolClause::Must->value] ?? []) === 1) {
            return $this->clauses[BoolClause::Must->value][0]->toArray();
        }

        $bool = array_map(
            static fn (array $queries): array => array_map(
                static fn (BuilderInterface $query): array => $query->toArray(),
                $queries
            ),
            $this->clauses
        );

        // An empty array would be encoded as [], which Elasticsearch rejects as the body of a bool query.
        return ['bool' => $bool === [] ? new \stdClass() : $bool];
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class BoolQuery implements BuilderInterface, OngrBuilderInterface
{
    public const MUST = 'must';
    public const FILTER = 'filter';
    public const SHOULD = 'should';
    public const MUST_NOT = 'must_not';

    /** @var array<string, BuilderInterface[]> */
    private array $clauses = [];

    public function add(BuilderInterface $query, string $type): void
    {
        $this->clauses[$type][] = $query;
    }

    public function toArray(): array
    {
        // A lone must clause is emitted as the query itself, so a builder without any filters sends a plain
        // match_all instead of wrapping it in a bool.
        if (count($this->clauses) === 1 && count($this->clauses[self::MUST] ?? []) === 1) {
            return $this->clauses[self::MUST][0]->toArray();
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

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'bool';
    }
}

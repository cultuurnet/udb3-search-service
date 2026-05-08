<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class BoolQuery implements BuilderInterface
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
        $bool = [];

        foreach ($this->clauses as $type => $queries) {
            if (empty($queries)) {
                continue;
            }
            $bool[$type] = array_map(
                static fn (BuilderInterface $q): array => $q->toArray(),
                $queries
            );
        }

        return ['bool' => $bool];
    }
}

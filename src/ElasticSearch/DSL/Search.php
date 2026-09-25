<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

final class Search
{
    /** @var BuilderInterface[] */
    private array $sorts = [];

    /** @var array<string, BuilderInterface> */
    private array $aggregations = [];

    public function __construct(
        private readonly BuilderInterface $query,
        private int $from,
        private int $size
    ) {
    }

    public function setFrom(int $from): void
    {
        $this->from = $from;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function addSort(BuilderInterface $sort): void
    {
        $this->sorts[] = $sort;
    }

    public function addAggregation(string $name, BuilderInterface $aggregation): void
    {
        $this->aggregations[$name] = $aggregation;
    }

    public function toArray(): array
    {
        $search = ['query' => $this->query->toArray()];

        if ($this->sorts !== []) {
            $search['sort'] = array_map(
                static fn (BuilderInterface $sort): array => $sort->toArray(),
                $this->sorts
            );
        }

        if ($this->aggregations !== []) {
            $search['aggregations'] = array_map(
                static fn (BuilderInterface $aggregation): array => $aggregation->toArray(),
                $this->aggregations
            );
        }

        $search['from'] = $this->from;
        $search['size'] = $this->size;

        return $search;
    }
}

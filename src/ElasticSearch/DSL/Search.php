<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

final class Search
{
    private int $from = 0;

    private int $size = 10;

    /** @var BuilderInterface[] */
    private array $queries = [];

    /** @var BuilderInterface[] */
    private array $sorts = [];

    /** @var BuilderInterface[] */
    private array $aggregations = [];

    public function addQuery(BuilderInterface $query): void
    {
        $this->queries[] = $query;
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

    public function addAggregation(BuilderInterface $aggregation): void
    {
        $this->aggregations[] = $aggregation;
    }

    public function toArray(): array
    {
        $output = [
            'from' => $this->from,
            'size' => $this->size,
        ];

        if (count($this->queries) === 1) {
            $output['query'] = $this->queries[0]->toArray();
        } elseif (count($this->queries) > 1) {
            $must = [];
            foreach ($this->queries as $query) {
                $must[] = $query->toArray();
            }
            $output['query'] = ['bool' => ['must' => $must]];
        }

        if (!empty($this->sorts)) {
            $sortArray = [];
            foreach ($this->sorts as $sort) {
                $sortArray[] = $sort->toArray();
            }
            $output['sort'] = $sortArray;
        }

        if (!empty($this->aggregations)) {
            $aggs = [];
            foreach ($this->aggregations as $aggregation) {
                $aggs = array_merge($aggs, $aggregation->toArray());
            }
            $output['aggs'] = $aggs;
        }

        return $output;
    }
}

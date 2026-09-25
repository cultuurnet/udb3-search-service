<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\CardinalityAggregation;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\TermsAggregation;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort\FieldSort;
use CultuurNet\UDB3\Search\SortOrder;
use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_search_with_only_a_query(): void
    {
        $search = new Search(query: new MatchAllQuery(), from: 0, size: 30);

        $expected = [
            'query' => ['match_all' => new \stdClass()],
            'from' => 0,
            'size' => 30,
        ];

        $this->assertSame(json_encode($expected), json_encode($search->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_a_search_with_sorts_and_aggregations(): void
    {
        $search = new Search(query: new MatchAllQuery(), from: 0, size: 30);
        $search->addSort(new FieldSort(field: 'created', order: SortOrder::Desc));
        $search->addSort(new FieldSort(field: '_score', order: SortOrder::Asc));
        $search->addAggregation(name: 'regions', aggregation: new TermsAggregation(field: 'regions.keyword'));
        $search->addAggregation(name: 'total', aggregation: new CardinalityAggregation(field: 'productionCollapseValue'));

        $expected = [
            'query' => ['match_all' => new \stdClass()],
            'sort' => [
                ['created' => ['order' => 'desc']],
                ['_score' => ['order' => 'asc']],
            ],
            'aggregations' => [
                'regions' => ['terms' => ['field' => 'regions.keyword']],
                'total' => ['cardinality' => ['field' => 'productionCollapseValue']],
            ],
            'from' => 0,
            'size' => 30,
        ];

        $this->assertSame(json_encode($expected), json_encode($search->toArray()));
    }

    /**
     * @test
     */
    public function it_changes_from_and_size(): void
    {
        $search = new Search(query: new MatchAllQuery(), from: 0, size: 30);
        $search->setFrom(20);
        $search->setSize(10);

        $expected = [
            'query' => ['match_all' => new \stdClass()],
            'from' => 20,
            'size' => 10,
        ];

        $this->assertSame(20, $search->getFrom());
        $this->assertSame(10, $search->getSize());
        $this->assertSame(json_encode($expected), json_encode($search->toArray()));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound\BoolQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort\FieldSort;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Bucketing\TermsAggregation;
use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_from_and_size(): void
    {
        $search = new Search();
        $search->setFrom(10);
        $search->setSize(20);

        $this->assertSame(10, $search->getFrom());
        $this->assertSame(20, $search->getSize());
    }

    /**
     * @test
     */
    public function it_produces_from_and_size_in_array(): void
    {
        $search = new Search();
        $search->setFrom(5);
        $search->setSize(15);

        $result = $search->toArray();

        $this->assertSame(5, $result['from']);
        $this->assertSame(15, $result['size']);
    }

    /**
     * @test
     */
    public function it_includes_a_single_query(): void
    {
        $search = new Search();
        $search->setFrom(0);
        $search->setSize(10);

        $boolQuery = new BoolQuery();
        $boolQuery->add(new MatchAllQuery(), BoolQuery::MUST);
        $boolQuery->add(new MatchAllQuery(), BoolQuery::MUST);
        $search->addQuery($boolQuery);

        $result = $search->toArray();

        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('bool', $result['query']);
    }

    /**
     * @test
     */
    public function it_includes_sorts(): void
    {
        $search = new Search();
        $search->setFrom(0);
        $search->setSize(10);
        $search->addSort(new FieldSort('created', 'desc'));

        $result = $search->toArray();

        $this->assertArrayHasKey('sort', $result);
        $this->assertCount(1, $result['sort']);
        $this->assertSame(['created' => ['order' => 'desc']], $result['sort'][0]);
    }

    /**
     * @test
     */
    public function it_includes_aggregations(): void
    {
        $search = new Search();
        $search->setFrom(0);
        $search->setSize(10);
        $search->addAggregation(new TermsAggregation('types', 'typeIds'));

        $result = $search->toArray();

        $this->assertArrayHasKey('aggregations', $result);
        $this->assertArrayHasKey('types', $result['aggregations']);
    }

    /**
     * @test
     */
    public function it_omits_sort_and_aggs_when_empty(): void
    {
        $search = new Search();
        $search->setFrom(0);
        $search->setSize(10);

        $result = $search->toArray();

        $this->assertArrayNotHasKey('sort', $result);
        $this->assertArrayNotHasKey('aggregations', $result);
    }
}

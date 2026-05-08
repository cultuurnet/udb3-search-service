<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use PHPUnit\Framework\TestCase;

final class BoolQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_bool_with_must_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolQuery::MUST);

        $result = $query->toArray();

        $this->assertArrayHasKey('bool', $result);
        $this->assertArrayHasKey('must', $result['bool']);
        $this->assertCount(1, $result['bool']['must']);
        $this->assertArrayNotHasKey('filter', $result['bool']);
        $this->assertArrayNotHasKey('should', $result['bool']);
        $this->assertArrayNotHasKey('must_not', $result['bool']);
    }

    /**
     * @test
     */
    public function it_omits_empty_clause_arrays(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolQuery::MUST);
        $query->add(new TermQuery('status', 'available'), BoolQuery::FILTER);

        $result = $query->toArray();

        $this->assertArrayHasKey('must', $result['bool']);
        $this->assertArrayHasKey('filter', $result['bool']);
        $this->assertArrayNotHasKey('should', $result['bool']);
        $this->assertArrayNotHasKey('must_not', $result['bool']);
    }

    /**
     * @test
     */
    public function it_supports_all_clause_types(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolQuery::MUST);
        $query->add(new TermQuery('field1', 'value1'), BoolQuery::FILTER);
        $query->add(new TermQuery('field2', 'value2'), BoolQuery::SHOULD);
        $query->add(new TermQuery('field3', 'value3'), BoolQuery::MUST_NOT);

        $result = $query->toArray();

        $this->assertArrayHasKey('must', $result['bool']);
        $this->assertArrayHasKey('filter', $result['bool']);
        $this->assertArrayHasKey('should', $result['bool']);
        $this->assertArrayHasKey('must_not', $result['bool']);
    }

    /**
     * @test
     */
    public function it_produces_empty_bool_when_no_clauses_added(): void
    {
        $query = new BoolQuery();

        $result = $query->toArray();

        $this->assertSame(['bool' => []], $result);
    }

    /**
     * @test
     */
    public function it_collects_multiple_queries_under_same_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('field1', 'a'), BoolQuery::FILTER);
        $query->add(new TermQuery('field2', 'b'), BoolQuery::FILTER);

        $result = $query->toArray();

        $this->assertCount(2, $result['bool']['filter']);
    }
}

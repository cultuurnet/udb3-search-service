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
        $query->add(new TermQuery('status', 'available'), BoolQuery::MUST);

        $expected = [
            'bool' => [
                'must' => [
                    ['match_all' => new \stdClass()],
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_collapses_single_must_with_no_other_clauses(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolQuery::MUST);

        $this->assertEquals(['match_all' => new \stdClass()], $query->toArray());
    }

    /**
     * @test
     */
    public function it_only_emits_used_clause_types(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolQuery::MUST);
        $query->add(new TermQuery('status', 'available'), BoolQuery::FILTER);

        $expected = [
            'bool' => [
                'must' => [
                    ['match_all' => new \stdClass()],
                ],
                'filter' => [
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
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

        $expected = [
            'bool' => [
                'must' => [
                    ['match_all' => new \stdClass()],
                ],
                'filter' => [
                    ['term' => ['field1' => 'value1']],
                ],
                'should' => [
                    ['term' => ['field2' => 'value2']],
                ],
                'must_not' => [
                    ['term' => ['field3' => 'value3']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_produces_empty_bool_when_no_clauses_added(): void
    {
        $query = new BoolQuery();

        $this->assertEquals(['bool' => new \stdClass()], $query->toArray());
    }

    /**
     * @test
     */
    public function it_collects_multiple_queries_under_same_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('field1', 'a'), BoolQuery::FILTER);
        $query->add(new TermQuery('field2', 'b'), BoolQuery::FILTER);

        $expected = [
            'bool' => [
                'filter' => [
                    ['term' => ['field1' => 'a']],
                    ['term' => ['field2' => 'b']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_collapse_single_filter_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('status', 'available'), BoolQuery::FILTER);

        $expected = [
            'bool' => [
                'filter' => [
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_collapse_single_should_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('status', 'available'), BoolQuery::SHOULD);

        $expected = [
            'bool' => [
                'should' => [
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_collapse_single_must_when_combined_with_must_not(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('type', 'event'), BoolQuery::MUST);
        $query->add(new TermQuery('hidden', 'true'), BoolQuery::MUST_NOT);

        $expected = [
            'bool' => [
                'must' => [
                    ['term' => ['type' => 'event']],
                ],
                'must_not' => [
                    ['term' => ['hidden' => 'true']],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_rejects_an_unsupported_clause_type(): void
    {
        $query = new BoolQuery();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The bool clause type "filters" is not supported.');

        $query->add(new MatchAllQuery(), 'filters');
    }
}

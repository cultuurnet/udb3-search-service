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
        $query->add(new MatchAllQuery(), BoolClause::Must);
        $query->add(new TermQuery('status', 'available'), BoolClause::Must);

        $expected = [
            'bool' => [
                'must' => [
                    ['match_all' => new \stdClass()],
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertSame(json_encode($expected), json_encode($query->toArray()));
    }

    /**
     * @test
     */
    public function it_collapses_single_must_with_no_other_clauses(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolClause::Must);

        $this->assertSame(json_encode(['match_all' => new \stdClass()]), json_encode($query->toArray()));
    }

    /**
     * @test
     */
    public function it_only_emits_used_clause_types(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolClause::Must);
        $query->add(new TermQuery('status', 'available'), BoolClause::Filter);

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

        $this->assertSame(json_encode($expected), json_encode($query->toArray()));
    }

    /**
     * @test
     */
    public function it_supports_all_clause_types(): void
    {
        $query = new BoolQuery();
        $query->add(new MatchAllQuery(), BoolClause::Must);
        $query->add(new TermQuery('field1', 'value1'), BoolClause::Filter);
        $query->add(new TermQuery('field2', 'value2'), BoolClause::Should);
        $query->add(new TermQuery('field3', 'value3'), BoolClause::MustNot);

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

        $this->assertSame(json_encode($expected), json_encode($query->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_empty_bool_when_no_clauses_added(): void
    {
        $query = new BoolQuery();

        $this->assertSame(json_encode(['bool' => new \stdClass()]), json_encode($query->toArray()));
    }

    /**
     * @test
     */
    public function it_collects_multiple_queries_under_same_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('field1', 'a'), BoolClause::Filter);
        $query->add(new TermQuery('field2', 'b'), BoolClause::Filter);

        $expected = [
            'bool' => [
                'filter' => [
                    ['term' => ['field1' => 'a']],
                    ['term' => ['field2' => 'b']],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_collapse_single_filter_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('status', 'available'), BoolClause::Filter);

        $expected = [
            'bool' => [
                'filter' => [
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_collapse_single_should_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('status', 'available'), BoolClause::Should);

        $expected = [
            'bool' => [
                'should' => [
                    ['term' => ['status' => 'available']],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_collapse_single_must_when_combined_with_must_not(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('type', 'event'), BoolClause::Must);
        $query->add(new TermQuery('hidden', 'true'), BoolClause::MustNot);

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

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_preserves_a_single_must_not_clause(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('status', 'deleted'), BoolClause::MustNot);

        $expected = [
            'bool' => [
                'must_not' => [
                    ['term' => ['status' => 'deleted']],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_emits_clauses_in_the_order_they_were_first_added(): void
    {
        $query = new BoolQuery();
        $query->add(new TermQuery('field1', 'value1'), BoolClause::Should);
        $query->add(new TermQuery('field2', 'value2'), BoolClause::Must);
        $query->add(new TermQuery('field3', 'value3'), BoolClause::Filter);
        $query->add(new TermQuery('field4', 'value4'), BoolClause::MustNot);
        $query->add(new TermQuery('field5', 'value5'), BoolClause::Should);

        $expected = [
            'bool' => [
                'should' => [
                    ['term' => ['field1' => 'value1']],
                    ['term' => ['field5' => 'value5']],
                ],
                'must' => [
                    ['term' => ['field2' => 'value2']],
                ],
                'filter' => [
                    ['term' => ['field3' => 'value3']],
                ],
                'must_not' => [
                    ['term' => ['field4' => 'value4']],
                ],
            ],
        ];

        $this->assertSame(json_encode($expected), json_encode($query->toArray()));
    }

    /**
     * @test
     */
    public function it_serializes_a_bool_inside_a_bool(): void
    {
        $inner = new BoolQuery();
        $inner->add(new TermQuery('status', 'available'), BoolClause::Filter);

        $outer = new BoolQuery();
        $outer->add(new MatchAllQuery(), BoolClause::Must);
        $outer->add($inner, BoolClause::Filter);

        $expected = [
            'bool' => [
                'must' => [
                    ['match_all' => new \stdClass()],
                ],
                'filter' => [
                    [
                        'bool' => [
                            'filter' => [
                                ['term' => ['status' => 'available']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(json_encode($expected), json_encode($outer->toArray()));
    }

    /**
     * @test
     */
    public function it_collapses_a_single_must_bool_inside_a_bool(): void
    {
        $inner = new BoolQuery();
        $inner->add(new TermQuery('status', 'available'), BoolClause::Must);

        $outer = new BoolQuery();
        $outer->add(new MatchAllQuery(), BoolClause::Must);
        $outer->add($inner, BoolClause::Filter);

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

        $this->assertSame(json_encode($expected), json_encode($outer->toArray()));
    }
}

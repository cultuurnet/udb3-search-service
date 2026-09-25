<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Joining;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound\BoolClause;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound\BoolQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use PHPUnit\Framework\TestCase;

final class NestedQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_nested_query(): void
    {
        $innerQuery = new TermQuery('subEvent.status', 'available');
        $query = new NestedQuery('subEvent', $innerQuery);

        $expected = [
            'nested' => [
                'path' => 'subEvent',
                'query' => ['term' => ['subEvent.status' => 'available']],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_serializes_a_bool_inside_a_nested_query_inside_a_bool(): void
    {
        $inner = new BoolQuery();
        $inner->add(new TermQuery('subEvent.status', 'available'), BoolClause::Filter);
        $inner->add(new TermQuery('subEvent.bookingAvailability', 'available'), BoolClause::Filter);

        $outer = new BoolQuery();
        $outer->add(new MatchAllQuery(), BoolClause::Must);
        $outer->add(new NestedQuery('subEvent', $inner), BoolClause::Filter);

        $expected = [
            'bool' => [
                'must' => [
                    ['match_all' => new \stdClass()],
                ],
                'filter' => [
                    [
                        'nested' => [
                            'path' => 'subEvent',
                            'query' => [
                                'bool' => [
                                    'filter' => [
                                        ['term' => ['subEvent.status' => 'available']],
                                        ['term' => ['subEvent.bookingAvailability' => 'available']],
                                    ],
                                ],
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
    public function it_collapses_a_single_must_bool_inside_a_nested_query(): void
    {
        $inner = new BoolQuery();
        $inner->add(new TermQuery('subEvent.status', 'available'), BoolClause::Must);

        $query = new NestedQuery('subEvent', $inner);

        $expected = [
            'nested' => [
                'path' => 'subEvent',
                'query' => ['term' => ['subEvent.status' => 'available']],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use PHPUnit\Framework\TestCase;

final class QueryStringQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_query_string_with_no_parameters(): void
    {
        $query = new QueryStringQuery('my query');

        $this->assertSame(['query_string' => ['query' => 'my query']], $query->toArray());
    }

    /**
     * @test
     */
    public function it_spreads_extra_parameters_into_query_string(): void
    {
        $query = new QueryStringQuery('foo bar', [
            'fields' => ['title', 'description'],
            'default_operator' => 'AND',
        ]);

        $expected = [
            'query_string' => [
                'query' => 'foo bar',
                'fields' => ['title', 'description'],
                'default_operator' => 'AND',
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }
}

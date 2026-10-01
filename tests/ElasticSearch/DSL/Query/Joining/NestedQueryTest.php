<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Joining;

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
}

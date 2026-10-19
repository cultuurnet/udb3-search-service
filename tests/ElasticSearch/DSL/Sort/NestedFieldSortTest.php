<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use CultuurNet\UDB3\Search\SortOrder;
use PHPUnit\Framework\TestCase;

final class NestedFieldSortTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_nested_field_sort(): void
    {
        $sort = new NestedFieldSort(
            field: 'metadata.recommendationFor.score',
            order: SortOrder::Desc,
            path: 'metadata.recommendationFor',
            filter: new TermQuery('metadata.recommendationFor.event', 'event-123')
        );

        $expected = [
            'metadata.recommendationFor.score' => [
                'order' => 'desc',
                'nested' => [
                    'path' => 'metadata.recommendationFor',
                    'filter' => ['term' => ['metadata.recommendationFor.event' => 'event-123']],
                ],
            ],
        ];

        $this->assertSame($expected, $sort->toArray());
    }
}

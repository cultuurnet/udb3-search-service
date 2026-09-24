<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery as OngrTermQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort as OngrFieldSort;
use PHPUnit\Framework\TestCase;

final class NestedFieldSortParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_nested_field_sort_identically_to_ongr(): void
    {
        $ongr = new OngrFieldSort('metadata.recommendationFor.score', 'desc', [
            'nested' => [
                'path' => 'metadata.recommendationFor',
                'filter' => (new OngrTermQuery('metadata.recommendationFor.event', 'event-123'))->toArray(),
            ],
        ]);
        $custom = new NestedFieldSort(
            field: 'metadata.recommendationFor.score',
            order: SortOrder::Desc,
            path: 'metadata.recommendationFor',
            filter: new TermQuery('metadata.recommendationFor.event', 'event-123')
        );

        // ongr appends the order after the other parameters; key order has no meaning to Elasticsearch.
        $this->assertEquals($ongr->toArray(), $custom->toArray());
    }
}

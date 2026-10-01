<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Sort\FieldSort as OngrFieldSort;
use PHPUnit\Framework\TestCase;

final class FieldSortParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_field_sort_identically_to_ongr(): void
    {
        $ongr = new OngrFieldSort('created', 'desc');
        $custom = new FieldSort(field: 'created', order: SortOrder::Desc);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

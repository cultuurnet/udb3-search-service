<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\SortOrder;
use PHPUnit\Framework\TestCase;

final class FieldSortTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_field_sort(): void
    {
        $sort = new FieldSort(field: 'created', order: SortOrder::Desc);

        $this->assertSame(['created' => ['order' => 'desc']], $sort->toArray());
    }
}

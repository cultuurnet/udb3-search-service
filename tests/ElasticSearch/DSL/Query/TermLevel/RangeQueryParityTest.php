<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery as OngrRangeQuery;
use PHPUnit\Framework\TestCase;

final class RangeQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_range_query_with_gte_and_lte_identically_to_ongr(): void
    {
        $ongr = new OngrRangeQuery('price', [OngrRangeQuery::GTE => 10, OngrRangeQuery::LTE => 50]);
        $custom = new RangeQuery('price', [RangeQuery::GTE => 10, RangeQuery::LTE => 50]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_range_query_with_gte_only_identically_to_ongr(): void
    {
        $ongr = new OngrRangeQuery('age', [OngrRangeQuery::GTE => 18]);
        $custom = new RangeQuery('age', [RangeQuery::GTE => 18]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_range_query_with_gt_and_lt_identically_to_ongr(): void
    {
        $ongr = new OngrRangeQuery('age', [OngrRangeQuery::GT => 18, OngrRangeQuery::LT => 65]);
        $custom = new RangeQuery('age', [RangeQuery::GT => 18, RangeQuery::LT => 65]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_range_query_with_gte_taking_priority_over_gt_identically_to_ongr(): void
    {
        $ongr = new OngrRangeQuery('age', [OngrRangeQuery::GTE => 18, OngrRangeQuery::GT => 17]);
        $custom = new RangeQuery('age', [RangeQuery::GTE => 18, RangeQuery::GT => 17]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_range_query_with_lte_taking_priority_over_lt_identically_to_ongr(): void
    {
        $ongr = new OngrRangeQuery('age', [OngrRangeQuery::LTE => 65, OngrRangeQuery::LT => 66]);
        $custom = new RangeQuery('age', [RangeQuery::LTE => 65, RangeQuery::LT => 66]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

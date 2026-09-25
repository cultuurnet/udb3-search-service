<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use ONGR\ElasticsearchDSL\Aggregation\Metric\CardinalityAggregation as OngrCardinalityAggregation;
use PHPUnit\Framework\TestCase;

final class CardinalityAggregationParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_cardinality_aggregation_identically_to_ongr(): void
    {
        $ongr = new OngrCardinalityAggregation('total');
        $ongr->setField('productionCollapseValue');
        $custom = new CardinalityAggregation(field: 'productionCollapseValue');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

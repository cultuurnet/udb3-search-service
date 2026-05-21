<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Metric;

use ONGR\ElasticsearchDSL\Aggregation\Metric\CardinalityAggregation as OngrCardinalityAggregation;
use PHPUnit\Framework\TestCase;

/**
 * Parity tests between the custom CardinalityAggregation and the ongr equivalent.
 *
 * Intentional divergences from ongr:
 *   - field is a required constructor parameter (ongr uses setField() inherited from AbstractAggregation)
 *   - precision_threshold, rehash, and script are not implemented (unused in this codebase)
 */
final class CardinalityAggregationParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_cardinality_aggregation_identically_to_ongr(): void
    {
        $ongr = new OngrCardinalityAggregation('total');
        $ongr->setField('productionCollapseValue');

        $custom = new CardinalityAggregation('total', 'productionCollapseValue');

        $this->assertSame($ongr->getName(), $custom->getName());
        $this->assertSame($ongr->toArray(), $custom->toArray());
    }
}

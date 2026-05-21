<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Metric;

use PHPUnit\Framework\TestCase;

final class CardinalityAggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_cardinality_aggregation(): void
    {
        $aggregation = new CardinalityAggregation('total');
        $aggregation->setField('productionCollapseValue');

        $this->assertSame('total', $aggregation->getName());
        $this->assertSame(
            ['cardinality' => ['field' => 'productionCollapseValue']],
            $aggregation->toArray()
        );
    }
}

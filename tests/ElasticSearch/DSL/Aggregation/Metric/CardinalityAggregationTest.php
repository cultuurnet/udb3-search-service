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

        $expected = [
            'total' => [
                'cardinality' => [
                    'field' => 'productionCollapseValue',
                ],
            ],
        ];

        $this->assertSame($expected, $aggregation->toArray());
    }
}

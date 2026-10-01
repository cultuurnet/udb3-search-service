<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use PHPUnit\Framework\TestCase;

final class CardinalityAggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_cardinality_aggregation(): void
    {
        $aggregation = new CardinalityAggregation(field: 'productionCollapseValue');

        $this->assertSame(['cardinality' => ['field' => 'productionCollapseValue']], $aggregation->toArray());
    }
}

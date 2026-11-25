<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use PHPUnit\Framework\TestCase;

final class TopHitsAggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_top_hits_aggregation(): void
    {
        $aggregation = new TopHitsAggregation(size: 1, source: ['location.name', 'geo_point']);

        $this->assertSame(
            ['top_hits' => ['size' => 1, '_source' => ['location.name', 'geo_point']]],
            $aggregation->toArray()
        );
    }
}

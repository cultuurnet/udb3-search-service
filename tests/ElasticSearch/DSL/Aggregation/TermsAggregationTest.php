<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use PHPUnit\Framework\TestCase;

final class TermsAggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_terms_aggregation(): void
    {
        $aggregation = new TermsAggregation(field: 'regions.keyword');

        $this->assertSame(['terms' => ['field' => 'regions.keyword']], $aggregation->toArray());
    }

    /**
     * @test
     */
    public function it_produces_a_terms_aggregation_with_a_size(): void
    {
        $aggregation = new TermsAggregation(field: 'regions.keyword', size: 50);

        $this->assertSame(['terms' => ['field' => 'regions.keyword', 'size' => 50]], $aggregation->toArray());
    }
}

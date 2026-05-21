<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Bucketing;

use PHPUnit\Framework\TestCase;

final class TermsAggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_terms_aggregation(): void
    {
        $aggregation = new TermsAggregation('types', 'typeIds');

        $this->assertSame('types', $aggregation->getName());
        $this->assertSame(['terms' => ['field' => 'typeIds']], $aggregation->toArray());
    }

    /**
     * @test
     */
    public function it_includes_extra_parameters(): void
    {
        $aggregation = new TermsAggregation('labels', 'labels.keyword');
        $aggregation->addParameter('size', 200);

        $expected = [
            'terms' => [
                'field' => 'labels.keyword',
                'size' => 200,
            ],
        ];

        $this->assertSame($expected, $aggregation->toArray());
    }
}

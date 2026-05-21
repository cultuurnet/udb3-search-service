<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Bucketing;

use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation as OngrTermsAggregation;
use PHPUnit\Framework\TestCase;

final class TermsAggregationParityTest extends TestCase
{
    /**
     * @test
     * Both ongr and the custom implementation return inner-only content from toArray().
     * The aggregation name is applied externally by the caller.
     */
    public function it_produces_equivalent_aggregations_structure(): void
    {
        $ongr = new OngrTermsAggregation('types', 'typeIds');
        $custom = new TermsAggregation('types', 'typeIds');

        $this->assertSame($ongr->getName(), $custom->getName());
        $this->assertSame($ongr->toArray(), $custom->toArray());
    }

    /**
     * @test
     */
    public function it_includes_extra_parameter_identically_to_ongr(): void
    {
        $ongr = new OngrTermsAggregation('labels', 'labels.keyword');
        $ongr->addParameter('size', 200);

        $custom = new TermsAggregation('labels', 'labels.keyword');
        $custom->addParameter('size', 200);

        $this->assertSame($ongr->toArray(), $custom->toArray());
    }
}

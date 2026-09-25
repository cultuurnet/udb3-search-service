<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation as OngrTermsAggregation;
use PHPUnit\Framework\TestCase;

final class TermsAggregationParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_terms_aggregation_identically_to_ongr(): void
    {
        $ongr = new OngrTermsAggregation('regions', 'regions.keyword');
        $custom = new TermsAggregation(field: 'regions.keyword');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_a_terms_aggregation_with_a_size_identically_to_ongr(): void
    {
        $ongr = new OngrTermsAggregation('regions', 'regions.keyword');
        $ongr->addParameter('size', 50);
        $custom = new TermsAggregation(field: 'regions.keyword', size: 50);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

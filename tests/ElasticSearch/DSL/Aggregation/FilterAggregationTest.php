<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\ExistsQuery;
use PHPUnit\Framework\TestCase;

final class FilterAggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_filter_aggregation_with_sub_aggregations(): void
    {
        $aggregation = new FilterAggregation(
            filter: new ExistsQuery('geo_point'),
            aggregations: ['places' => new TermsAggregation(field: 'location.mainId.keyword')]
        );

        $this->assertSame(
            [
                'filter' => ['exists' => ['field' => 'geo_point']],
                'aggregations' => [
                    'places' => ['terms' => ['field' => 'location.mainId.keyword']],
                ],
            ],
            $aggregation->toArray()
        );
    }
}

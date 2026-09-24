<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use PHPUnit\Framework\TestCase;

final class GeoShapeQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_shape_query_for_a_pre_indexed_shape(): void
    {
        $query = new GeoShapeQuery('geo', 'region-1', 'regions-index', 'location');

        $expected = [
            'geo_shape' => [
                'geo' => [
                    'indexed_shape' => [
                        'id' => 'region-1',
                        'index' => 'regions-index',
                        'path' => 'location',
                    ],
                    'relation' => 'intersects',
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }
}

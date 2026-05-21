<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use PHPUnit\Framework\TestCase;

final class GeoDistanceQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_distance_query(): void
    {
        $location = (object) ['lat' => 50.85, 'lon' => 4.35];
        $query = new GeoDistanceQuery('geo_point', '10km', $location);

        $expected = [
            'geo_distance' => [
                'distance' => '10km',
                'geo_point' => (object) ['lat' => 50.85, 'lon' => 4.35],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_produces_geo_distance_query_with_string_location(): void
    {
        $query = new GeoDistanceQuery('geo_point', '10km', '50.85,4.35');

        $expected = [
            'geo_distance' => [
                'distance' => '10km',
                'geo_point' => '50.85,4.35',
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }
}

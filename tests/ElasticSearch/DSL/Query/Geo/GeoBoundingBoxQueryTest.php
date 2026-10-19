<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use PHPUnit\Framework\TestCase;

final class GeoBoundingBoxQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query(): void
    {
        $query = new GeoBoundingBoxQuery(
            'geo_point',
            new Coordinates(new Latitude(51.5), new Longitude(3.0)),
            new Coordinates(new Latitude(50.5), new Longitude(5.0))
        );

        $expected = [
            'geo_bounding_box' => [
                'geo_point' => [
                    'top_left' => ['lat' => 51.5, 'lon' => 3.0],
                    'bottom_right' => ['lat' => 50.5, 'lon' => 5.0],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }
}

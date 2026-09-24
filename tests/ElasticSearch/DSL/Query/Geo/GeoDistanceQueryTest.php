<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use PHPUnit\Framework\TestCase;

final class GeoDistanceQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_distance_query(): void
    {
        $query = new GeoDistanceQuery(
            'geo_point',
            '10km',
            new Coordinates(new Latitude(50.85), new Longitude(4.35))
        );

        $this->assertSame(
            '{"geo_distance":{"distance":"10km","geo_point":{"lat":50.85,"lon":4.35}}}',
            json_encode($query->toArray())
        );
    }
}

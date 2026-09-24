<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\SortOrder;
use PHPUnit\Framework\TestCase;

final class GeoDistanceSortTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_geo_distance_sort(): void
    {
        $sort = new GeoDistanceSort(
            field: 'geo_point',
            location: new Coordinates(new Latitude(50.85), new Longitude(4.35)),
            order: SortOrder::Asc
        );

        $expected = [
            '_geo_distance' => [
                'order' => 'asc',
                'geo_point' => ['lat' => 50.85, 'lon' => 4.35],
                'unit' => 'km',
                'distance_type' => 'plane',
            ],
        ];

        $this->assertSame($expected, $sort->toArray());
    }
}

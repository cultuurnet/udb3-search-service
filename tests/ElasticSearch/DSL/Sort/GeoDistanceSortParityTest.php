<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Sort\FieldSort as OngrFieldSort;
use PHPUnit\Framework\TestCase;

final class GeoDistanceSortParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_geo_distance_sort_identically_to_ongr(): void
    {
        $ongr = new OngrFieldSort('_geo_distance', 'asc', [
            'geo_point' => ['lat' => 50.85, 'lon' => 4.35],
            'unit' => 'km',
            'distance_type' => 'plane',
        ]);
        $custom = new GeoDistanceSort(
            field: 'geo_point',
            location: new Coordinates(new Latitude(50.85), new Longitude(4.35)),
            order: SortOrder::Asc
        );

        // ongr appends the order after the other parameters; key order has no meaning to Elasticsearch.
        $this->assertEquals($ongr->toArray(), $custom->toArray());
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery as OngrGeoBoundingBoxQuery;
use PHPUnit\Framework\TestCase;

final class GeoBoundingBoxQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_identically_to_ongr(): void
    {
        $ongr = new OngrGeoBoundingBoxQuery(
            'geo_point',
            [['lat' => 51.5, 'lon' => 3.0], ['lat' => 50.5, 'lon' => 5.0]]
        );
        $custom = new GeoBoundingBoxQuery(
            'geo_point',
            new Coordinates(new Latitude(51.5), new Longitude(3.0)),
            new Coordinates(new Latitude(50.5), new Longitude(5.0))
        );

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

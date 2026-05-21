<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery as OngrGeoDistanceQuery;
use PHPUnit\Framework\TestCase;

final class GeoDistanceQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_distance_query_identically_to_ongr(): void
    {
        $location = (object) ['lat' => 50.85, 'lon' => 4.35];

        $ongr = new OngrGeoDistanceQuery('geo_point', '10km', $location);
        $custom = new GeoDistanceQuery('geo_point', '10km', $location);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_geo_distance_query_with_string_location_identically_to_ongr(): void
    {
        $ongr = new OngrGeoDistanceQuery('geo_point', '10km', '50.85,4.35');
        $custom = new GeoDistanceQuery('geo_point', '10km', '50.85,4.35');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

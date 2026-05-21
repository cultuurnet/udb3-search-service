<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery as OngrGeoBoundingBoxQuery;
use PHPUnit\Framework\TestCase;

final class GeoBoundingBoxQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_identically_to_ongr(): void
    {
        $topLeft = ['lat' => 51.5, 'lon' => 3.0];
        $bottomRight = ['lat' => 50.5, 'lon' => 5.0];

        $ongr = new OngrGeoBoundingBoxQuery('geo_point', [$topLeft, $bottomRight]);
        $custom = new GeoBoundingBoxQuery('geo_point', [$topLeft, $bottomRight]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_with_named_keys_identically_to_ongr(): void
    {
        $topLeft = ['lat' => 51.5, 'lon' => 3.0];
        $bottomRight = ['lat' => 50.5, 'lon' => 5.0];

        $ongr = new OngrGeoBoundingBoxQuery('geo_point', ['top_left' => $topLeft, 'bottom_right' => $bottomRight]);
        $custom = new GeoBoundingBoxQuery('geo_point', ['top_left' => $topLeft, 'bottom_right' => $bottomRight]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_with_four_values_identically_to_ongr(): void
    {
        $ongr = new OngrGeoBoundingBoxQuery('geo_point', [51.5, 3.0, 50.5, 5.0]);
        $custom = new GeoBoundingBoxQuery('geo_point', [51.5, 3.0, 50.5, 5.0]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery as OngrGeoShapeQuery;
use PHPUnit\Framework\TestCase;

final class GeoShapeQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_shape_query_identically_to_ongr_except_for_the_removed_type(): void
    {
        $ongr = new OngrGeoShapeQuery();
        $ongr->addPreIndexedShape('geo', 'region-1', 'regions', 'regions-index', 'location');
        $custom = new GeoShapeQuery('geo', 'region-1', 'regions-index', 'location');

        // Elasticsearch 8 no longer supports mapping types, so the type ongr emits is intentionally dropped.
        $expected = $ongr->toArray();
        unset($expected['geo_shape']['geo']['indexed_shape']['type']);

        $this->assertSame(json_encode($expected), json_encode($custom->toArray()));
    }
}

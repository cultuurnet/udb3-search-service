<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery as OngrGeoShapeQuery;
use PHPUnit\Framework\TestCase;

final class GeoShapeQueryParityTest extends TestCase
{
    /**
     * @test
     * The custom implementation intentionally omits 'type' (ES8 removed it from indexed shape queries)
     * and 'relation' (ES8 defaults to 'intersects'). This test documents that known divergence.
     */
    public function it_omits_type_and_relation_from_pre_indexed_shape_unlike_ongr(): void
    {
        $ongr = new OngrGeoShapeQuery();
        $ongr->addPreIndexedShape('geo', 'region-1', 'region', 'regions-index', 'location');

        $custom = new GeoShapeQuery();
        $custom->addPreIndexedShape('geo', 'region-1', 'region', 'regions-index', 'location');

        $ongrOutput = $ongr->toArray();
        $customOutput = $custom->toArray();

        $this->assertArrayHasKey('type', $ongrOutput['geo_shape']['geo']['indexed_shape']);
        $this->assertArrayHasKey('relation', $ongrOutput['geo_shape']['geo']);
        $this->assertArrayNotHasKey('type', $customOutput['geo_shape']['geo']['indexed_shape']);
        $this->assertArrayNotHasKey('relation', $customOutput['geo_shape']['geo']);
    }
}

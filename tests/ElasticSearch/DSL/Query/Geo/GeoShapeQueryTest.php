<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use PHPUnit\Framework\TestCase;

final class GeoShapeQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_shape_query_without_type(): void
    {
        $query = new GeoShapeQuery();
        $query->addPreIndexedShape('geo', 'region-1', 'regions-index', 'location');

        $expected = [
            'geo_shape' => [
                'geo' => [
                    'indexed_shape' => [
                        'id' => 'region-1',
                        'index' => 'regions-index',
                        'path' => 'location',
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_include_type_in_output(): void
    {
        $query = new GeoShapeQuery();
        $query->addPreIndexedShape('geo', 'region-1', 'regions-index', 'location');

        $result = $query->toArray();

        $this->assertArrayNotHasKey('type', $result['geo_shape']['geo']['indexed_shape']);
    }

    /**
     * @test
     */
    public function it_throws_when_add_shape_is_called(): void
    {
        $this->expectException(\RuntimeException::class);

        $query = new GeoShapeQuery();
        $query->addShape('geo', 'polygon', []);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Geo;

use PHPUnit\Framework\TestCase;

final class GeoBoundingBoxQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query(): void
    {
        $topLeft = ['lat' => 51.5, 'lon' => 3.0];
        $bottomRight = ['lat' => 50.5, 'lon' => 5.0];

        $query = new GeoBoundingBoxQuery('geo_point', [$topLeft, $bottomRight]);

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

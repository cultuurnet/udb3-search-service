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

    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_with_named_keys(): void
    {
        $topLeft = ['lat' => 51.5, 'lon' => 3.0];
        $bottomRight = ['lat' => 50.5, 'lon' => 5.0];

        $query = new GeoBoundingBoxQuery('geo_point', ['top_left' => $topLeft, 'bottom_right' => $bottomRight]);

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

    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_with_four_values(): void
    {
        $query = new GeoBoundingBoxQuery('geo_point', [51.5, 3.0, 50.5, 5.0]);

        $expected = [
            'geo_bounding_box' => [
                'geo_point' => [
                    'top' => 51.5,
                    'left' => 3.0,
                    'bottom' => 50.5,
                    'right' => 5.0,
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_produces_geo_bounding_box_query_with_four_named_values(): void
    {
        $query = new GeoBoundingBoxQuery('geo_point', [
            'top' => 51.5,
            'left' => 3.0,
            'bottom' => 50.5,
            'right' => 5.0,
        ]);

        $expected = [
            'geo_bounding_box' => [
                'geo_point' => [
                    'top' => 51.5,
                    'left' => 3.0,
                    'bottom' => 50.5,
                    'right' => 5.0,
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_number_of_bounds(): void
    {
        $this->expectException(\LogicException::class);

        (new GeoBoundingBoxQuery('geo_point', [['lat' => 51.5, 'lon' => 3.0]]))->toArray();
    }
}

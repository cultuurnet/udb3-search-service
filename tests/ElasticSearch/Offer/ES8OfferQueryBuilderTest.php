<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;

final class ES8OfferQueryBuilderTest extends ElasticSearchOfferQueryBuilderTest
{
    protected function createBuilder(int $aggregationSize = null): OfferQueryBuilderInterface
    {
        return new ES8OfferQueryBuilder($aggregationSize);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geoshape_filter(): void
    {
        $builder = $this->createBuilder()
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withRegionFilter('geoshapes', 'regions', new RegionId('gem-leuven'))
            ->withRegionFilter('geoshapes', 'regions', new RegionId('prv-limburg'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'geo_shape' => [
                                'geo' => [
                                    'indexed_shape' => [
                                        'id' => 'gem-leuven',
                                        'index' => 'geoshapes',
                                        'path' => 'location',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'geo_shape' => [
                                'geo' => [
                                    'indexed_shape' => [
                                        'id' => 'prv-limburg',
                                        'index' => 'geoshapes',
                                        'path' => 'location',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedQueryArray, $builder->build());
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_multiple_sorts(): void
    {
        $builder = $this->createBuilder()
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSortByDistance(
                new Coordinates(
                    new Latitude(8.674),
                    new Longitude(50.23)
                ),
                SortOrder::asc()
            )
            ->withSortByAvailableTo(SortOrder::asc())
            ->withSortByScore(SortOrder::desc())
            ->withSortByPopularity(SortOrder::desc())
            ->withSortByRecommendationScore('6f11ca64-0b8b-45e8-8a99-9673f06935cc', SortOrder::asc());

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
            ],
            'sort' => [
                [
                    '_geo_distance' => [
                        'order' => 'asc',
                        'geo_point' => [
                            'lat' => 8.674,
                            'lon' => 50.23,
                        ],
                        'unit' => 'km',
                        'distance_type' => 'plane',
                    ],
                ],
                [
                    'availableTo' => [
                        'order' => 'asc',
                    ],
                ],
                [
                    '_score' => [
                        'order' => 'desc',
                    ],
                ],
                [
                    'metadata.popularity' => [
                        'order' => 'desc',
                    ],
                ],
                [
                    'metadata.recommendationFor.score' => [
                        'order' => 'asc',
                        'nested' => [
                            'path' => 'metadata.recommendationFor',
                            'filter' => [
                                'term' => [
                                    'metadata.recommendationFor.event' => '6f11ca64-0b8b-45e8-8a99-9673f06935cc',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedQueryArray, $builder->build());
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\Start;

final class ES8OrganizerQueryBuilderTest extends ElasticSearchOrganizerQueryBuilderTest
{
    protected function createBuilder(int $aggregationSize = null): OrganizerQueryBuilderInterface
    {
        return new ES8OrganizerQueryBuilder($aggregationSize);
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
}

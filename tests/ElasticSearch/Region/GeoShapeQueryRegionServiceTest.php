<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Region;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Region\RegionId;
use Elastic\Elasticsearch\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GeoShapeQueryRegionServiceTest extends TestCase
{
    private ClientInterface&MockObject $client;

    private string $geoShapesIndexName;

    private GeoShapeQueryRegionService $regionService;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->geoShapesIndexName = 'mock';

        $this->regionService = new GeoShapeQueryRegionService(
            $this->client,
            $this->geoShapesIndexName
        );
    }

    /**
     * @test
     */
    public function it_uses_a_percolate_query_and_returns_all_region_ids_of_the_matching_queries(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('search')
            ->withConsecutive(
                [
                    [
                        'index' => $this->geoShapesIndexName,
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        'match_all' => (object)[],
                                    ],
                                    'filter' => [
                                        'geo_shape' => [
                                            'location' => [
                                                'shape' => [
                                                    'type' => 'Point',
                                                    'coordinates' => [80.9, -4.5],
                                                ],
                                                'relation' => 'contains',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'size' => 10,
                        'from' => 0,
                    ],
                ],
                [
                    [
                        'index' => $this->geoShapesIndexName,
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        'match_all' => (object)[],
                                    ],
                                    'filter' => [
                                        'geo_shape' => [
                                            'location' => [
                                                'shape' => [
                                                    'type' => 'Point',
                                                    'coordinates' => [80.9, -4.5],
                                                ],
                                                'relation' => 'contains',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'size' => 10,
                        'from' => 10,
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/regions_1.json')),
                Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/regions_2.json'))
            );

        $expectedRegionIds = [
            new RegionId('gem-nieuwerkerken'),
            new RegionId('gem-oostkamp'),
            new RegionId('gem-oostrozebeke'),
            new RegionId('gem-opglabbeek'),
            new RegionId('gem-peer'),
            new RegionId('gem-pittem'),
            new RegionId('gem-putte'),
            new RegionId('gem-ronse'),
            new RegionId('gem-roosdaal'),
            new RegionId('gem-ruiselede'),
            new RegionId('gem-rumst'),
            new RegionId('gem-sint-amands'),
            new RegionId('gem-sint-genesius-rode'),
            new RegionId('gem-sint-laureins'),
            new RegionId('gem-ternat'),
            new RegionId('gem-tervuren'),
            new RegionId('gem-kalmthout'),
            new RegionId('gem-kinrooi'),
            new RegionId('gem-kluisbergen'),
            new RegionId('gem-kortenaken'),
        ];

        $actualRegionIds = $this->regionService->getRegionIds(
            [
                'type' => 'Point',
                'coordinates' => [80.9, -4.5],
            ]
        );

        $this->assertEquals($expectedRegionIds, $actualRegionIds);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_it_gets_an_invalid_response_from_elasticsearch(): void
    {
        $this->client->expects($this->once())
            ->method('search')
            ->willReturn(Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/regions_invalid.json')));

        $this->expectException(RuntimeException::class);

        $this->regionService->getRegionIds(
            [
                'type' => 'Point',
                'coordinates' => [80.9, -4.5],
            ]
        );
    }
}

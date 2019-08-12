<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class GeoShapeQueryOfferRegionServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var StringLiteral
     */
    private $geoShapesIndexName;

    /**
     * @var GeoShapeQueryOfferRegionService
     */
    private $offerRegionService;

    public function setUp()
    {
        $this->client = $this->createMock(Client::class);
        $this->geoShapesIndexName = new StringLiteral('mock');

        $this->offerRegionService = new GeoShapeQueryOfferRegionService(
            $this->client,
            $this->geoShapesIndexName
        );
    }

    /**
     * @test
     */
    public function it_uses_a_percolate_query_and_returns_all_region_ids_of_the_matching_queries()
    {
        $id = '0ec56b06-d854-4a4b-8aeb-4848433170bc';
        $jsonData = [
            '@id' => 'http://mock.io/event/0ec56b06-d854-4a4b-8aeb-4848433170bc',
            '@type' => 'Event',
            'geo' => [
                'type' => 'Point',
                'coordinates' => [80.9, -4.5],
            ],
        ];

        $jsonDocument = new JsonDocument($id, json_encode($jsonData));

        $this->client->expects($this->exactly(2))
            ->method('search')
            ->withConsecutive(
                [
                    [
                        'index' => $this->geoShapesIndexName->toNative(),
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        'match_all' => (object) [],
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
                        'index' => $this->geoShapesIndexName->toNative(),
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        'match_all' => (object) [],
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
                json_decode(file_get_contents(__DIR__ . '/data/regions_1.json'), true),
                json_decode(file_get_contents(__DIR__ . '/data/regions_2.json'), true)
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

        $actualRegionIds = $this->offerRegionService->getRegionIds(OfferType::EVENT(), $jsonDocument);

        $this->assertEquals($expectedRegionIds, $actualRegionIds);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_it_gets_an_invalid_response_from_elasticsearch()
    {
        $id = '0ec56b06-d854-4a4b-8aeb-4848433170bc';
        $jsonData = [
            '@id' => 'http://mock.io/event/0ec56b06-d854-4a4b-8aeb-4848433170bc',
            '@type' => 'Event',
            'geo' => [80.9, -4.5],
        ];

        $jsonDocument = new JsonDocument($id, json_encode($jsonData));

        $this->client->expects($this->once())
            ->method('search')
            ->willReturn(json_decode(file_get_contents(__DIR__ . '/data/regions_invalid.json'), true));

        $this->expectException(\RuntimeException::class);

        $this->offerRegionService->getRegionIds(OfferType::EVENT(), $jsonDocument);
    }
}

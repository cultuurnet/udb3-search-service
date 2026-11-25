<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\PlaceEventCount;
use CultuurNet\UDB3\Search\Offer\PlaceEventCounts;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Start;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ElasticSearchOfferSearchServiceTest extends TestCase
{
    /**
     * @var ElasticSearchClient&MockObject
     */
    private $client;

    private string $indexName;

    private ElasticSearchOfferSearchService $multiTypeService;

    private ElasticSearchOfferSearchService $singleTypeService;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(ElasticSearchClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = 'udb3-core';
        $this->multiTypeService = $this->createService('event,place');
        $this->singleTypeService = $this->createService('event');
    }

    private function createService(string $documentType): ElasticSearchOfferSearchService
    {
        return new ElasticSearchOfferSearchService(
            $this->client,
            $this->indexName,
            $documentType,
            new ElasticSearchPagedResultSetFactory(new NullAggregationTransformer())
        );
    }

    /**
     * @test
     */
    public function it_returns_a_paged_result_set_for_the_given_search_parameters(): void
    {
        $queryBuilder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(0), new Limit(2));

        $response = [
            'hits' => [
                'total' => ['value' => 32, 'relation' => 'eq'],
                'hits' => [
                    [
                        '_index' => 'udb3-core',
                        '_id' => '351b85c1-66ea-463b-82a6-515b7de0d267',
                        '_source' => [
                            '@id' => 'http://foo.bar/events/351b85c1-66ea-463b-82a6-515b7de0d267',
                            '@type' => 'Event',
                            'regions' => ['foo', 'bar'],
                            'originalEncodedJsonLd' => '{}',
                        ],
                    ],
                    [
                        '_index' => 'udb3-core',
                        '_id' => 'bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                        '_source' => [
                            '@id' => 'http://foo.bar/places/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            '@type' => 'Place',
                            'regions' => ['foo', 'bar'],
                            'originalEncodedJsonLd' => '{}',
                        ],
                    ],
                ],
            ],
        ];

        $this->client->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => $this->indexName,
                    'body' => [
                        '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions', 'typicalAgeRange', 'birthdateRange'],
                        'from' => 0,
                        'size' => 2,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['match_all' => (object) []],
                                ],
                                'filter' => [
                                    ['terms' => ['@type' => ['event', 'place']]],
                                ],
                            ],
                        ],
                        'track_total_hits' => true,
                    ],
                ]
            )
            ->willReturn($response);

        $expected = new PagedResultSet(
            32,
            2,
            [
                (new JsonDocument('351b85c1-66ea-463b-82a6-515b7de0d267'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/events/351b85c1-66ea-463b-82a6-515b7de0d267',
                            '@type' => 'Event',
                            'regions' => ['foo', 'bar'],
                            'originalEncodedJsonLd' => '{}',
                        ]
                    ),
                (new JsonDocument('bdc0f4ce-a211-463e-a8d1-d8b699fb1159'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/places/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            '@type' => 'Place',
                            'regions' => ['foo', 'bar'],
                            'originalEncodedJsonLd' => '{}',
                        ]
                    ),
            ]
        );

        $actual = $this->multiTypeService->search($queryBuilder);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_filters_on_a_single_type_using_a_term_query(): void
    {
        $queryBuilder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(0), new Limit(1));

        $response = [
            'hits' => [
                'total' => ['value' => 1, 'relation' => 'eq'],
                'hits' => [
                    [
                        '_index' => 'udb3-core',
                        '_id' => '351b85c1-66ea-463b-82a6-515b7de0d267',
                        '_source' => [
                            '@id' => 'http://foo.bar/events/351b85c1-66ea-463b-82a6-515b7de0d267',
                            '@type' => 'Event',
                            'regions' => [],
                            'originalEncodedJsonLd' => '{}',
                        ],
                    ],
                ],
            ],
        ];

        $this->client->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => $this->indexName,
                    'body' => [
                        '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions', 'typicalAgeRange', 'birthdateRange'],
                        'from' => 0,
                        'size' => 1,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['match_all' => (object) []],
                                ],
                                'filter' => [
                                    ['term' => ['@type' => 'event']],
                                ],
                            ],
                        ],
                        'track_total_hits' => true,
                    ],
                ]
            )
            ->willReturn($response);

        $actual = $this->singleTypeService->search($queryBuilder);

        $this->assertEquals(
            new PagedResultSet(
                1,
                1,
                [
                    (new JsonDocument('351b85c1-66ea-463b-82a6-515b7de0d267'))
                        ->withBody(
                            (object) [
                                '@id' => 'http://foo.bar/events/351b85c1-66ea-463b-82a6-515b7de0d267',
                                '@type' => 'Event',
                                'regions' => [],
                                'originalEncodedJsonLd' => '{}',
                            ]
                        ),
                ]
            ),
            $actual
        );
    }

    /**
     * @test
     */
    public function it_counts_events_per_place(): void
    {
        $queryBuilder = (new ElasticSearchOfferQueryBuilder())->withPlaceEventCounts(50);

        $this->client->method('search')->willReturn([
            'hits' => [
                'total' => ['value' => 12, 'relation' => 'eq'],
                'hits' => [],
            ],
            'aggregations' => [
                'placeEventCounts' => [
                    'doc_count' => 12,
                    'places' => [
                        'buckets' => [
                            $this->placeBucket('a', ['nl' => 'Het Depot'], 8),
                            $this->placeBucket('b', null, 4),
                        ],
                    ],
                ],
            ],
        ]);

        $expected = new PlaceEventCounts(
            12,
            new PlaceEventCount('https://io.uitdatabank.be/place/a', ['nl' => 'Het Depot'], 50.88, 4.7, 8),
            new PlaceEventCount('https://io.uitdatabank.be/place/b', [], 50.88, 4.7, 4)
        );

        $this->assertEquals($expected, $this->singleTypeService->countEventsPerPlace($queryBuilder));
    }

    private function placeBucket(string $placeId, ?array $name, int $eventCount): array
    {
        $location = ['@id' => 'https://io.uitdatabank.be/place/' . $placeId];
        if ($name !== null) {
            $location['name'] = $name;
        }

        return [
            'key' => $placeId,
            'doc_count' => $eventCount,
            'place' => [
                'hits' => [
                    'hits' => [
                        ['_source' => ['location' => $location, 'geo_point' => ['lat' => 50.88, 'lon' => 4.7]]],
                    ],
                ],
            ],
        ];
    }
}

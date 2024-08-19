<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Start;
use Elasticsearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ElasticSearchOfferSearchServiceTest extends TestCase
{
    /**
     * @var Client&MockObject
     */
    private $client;

    private string $indexName;

    private string $documentType;

    private ElasticSearchOfferSearchService $service;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = 'udb3-core';
        $this->documentType = 'event,place';

        $this->service = new ElasticSearchOfferSearchService(
            $this->client,
            $this->indexName,
            $this->documentType,
            new ElasticSearchPagedResultSetFactory(
                new NullAggregationTransformer()
            )
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
                'total' => 32,
                'hits' => [
                    [
                        '_index' => 'udb3-core',
                        '_type' => 'event',
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
                        '_type' => 'place',
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
                    'type' => $this->documentType,
                    'body' => [
                        '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
                        'from' => 0,
                        'size' => 2,
                        'query' => [
                            'match_all' => (object) [],
                        ],
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

        $actual = $this->service->search($queryBuilder);

        $this->assertEquals($expected, $actual);
    }
}

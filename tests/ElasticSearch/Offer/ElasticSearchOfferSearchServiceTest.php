<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchOfferSearchServiceTest extends TestCase
{
    /**
     * @var Client|MockObject
     */
    private $client;

    /**
     * @var StringLiteral
     */
    private $indexName;

    /**
     * @var StringLiteral
     */
    private $documentType;

    /**
     * @var ElasticSearchOfferSearchService
     */
    private $service;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = new StringLiteral('udb3-core');
        $this->documentType = new StringLiteral('event,place');

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
    public function it_returns_a_paged_result_set_for_the_given_search_parameters()
    {
        $queryBuilder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(0))
            ->withLimit(new Natural(2));

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
                            'name' => 'Punkfest',
                        ],
                    ],
                    [
                        '_index' => 'udb3-core',
                        '_type' => 'place',
                        '_id' => 'bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                        '_source' => [
                            '@id' => 'http://foo.bar/places/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            '@type' => 'Place',
                            'name' => 'Harmoniezaal Sint-Quintinus',
                        ],
                    ],
                ],
            ],
        ];

        $this->client->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => $this->indexName->toNative(),
                    'type' => $this->documentType->toNative(),
                    'body' => [
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
            new Natural(32),
            new Natural(2),
            [
                (new JsonDocument('351b85c1-66ea-463b-82a6-515b7de0d267'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/events/351b85c1-66ea-463b-82a6-515b7de0d267',
                            '@type' => 'Event',
                            'name' => 'Punkfest',
                        ]
                    ),
                (new JsonDocument('bdc0f4ce-a211-463e-a8d1-d8b699fb1159'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/places/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            '@type' => 'Place',
                            'name' => 'Harmoniezaal Sint-Quintinus',
                        ]
                    ),
            ]
        );

        $actual = $this->service->search($queryBuilder);

        $this->assertEquals($expected, $actual);
    }
}

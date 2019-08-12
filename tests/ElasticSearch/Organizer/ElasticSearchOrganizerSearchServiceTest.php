<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchOrganizerSearchServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
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
     * @var ElasticSearchOrganizerSearchService
     */
    private $service;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = new StringLiteral('udb3-core');
        $this->documentType = new StringLiteral('organizer');

        $this->service = new ElasticSearchOrganizerSearchService(
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
    public function it_returns_a_paged_result_set_for_the_given_search_query()
    {
        $queryBuilder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Natural(960))
            ->withLimit(new Natural(30))
            ->withAutoCompleteFilter(new StringLiteral('Collectief'));

        $idCollectiefCursief = '351b85c1-66ea-463b-82a6-515b7de0d267';

        $sourceCollectiefCursief = [
            '@id' => 'http://foo.bar/organizers/351b85c1-66ea-463b-82a6-515b7de0d267',
            'name' => 'Collectief Cursief',
        ];

        $idCollectiefAC = 'bdc0f4ce-a211-463e-a8d1-d8b699fb1159';

        $sourceAC = [
            '@id' => 'http://foo.bar/organizers/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
            'name' => 'Anoniem Collectief',
        ];

        $response = [
            'hits' => [
                'total' => 962,
                'hits' => [
                    [
                        '_index' => $this->indexName->toNative(),
                        '_type' => $this->documentType->toNative(),
                        '_id' => $idCollectiefCursief,
                        '_source' => $sourceCollectiefCursief,
                    ],
                    [
                        '_index' => $this->indexName->toNative(),
                        '_type' => $this->documentType->toNative(),
                        '_id' => $idCollectiefAC,
                        '_source' => $sourceAC,
                    ],
                ]
            ],
        ];

        $this->client->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => $this->indexName->toNative(),
                    'type' => $this->documentType->toNative(),
                    'body' => [
                        'from' => 960,
                        'size' => 30,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'match_all' => (object) [],
                                    ],
                                ],
                                'filter' => [
                                    [
                                        'match_phrase' => [
                                            'name.nl.autocomplete' => [
                                                'query' => 'Collectief'
                                            ],
                                        ],
                                    ],
                                ],
                                'should' => [
                                    [
                                        'match_phrase' => [
                                            'name.nl.autocomplete' => [
                                                'query' => 'Collectief'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->willReturn($response);

        $expectedResults = [
            (new JsonDocument($idCollectiefCursief))
                ->withBody((object) $sourceCollectiefCursief),
            (new JsonDocument($idCollectiefAC))
                ->withBody((object) $sourceAC),
        ];

        $expectedPagedResultSet = new PagedResultSet(
            new Natural(962),
            new Natural(30),
            $expectedResults
        );

        $actualPagedResultSet = $this->service->search($queryBuilder);

        $this->assertEquals($expectedPagedResultSet, $actualPagedResultSet);
    }
}

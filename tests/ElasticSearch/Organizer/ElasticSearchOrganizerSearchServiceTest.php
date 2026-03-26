<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Start;
use Elasticsearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ElasticSearchOrganizerSearchServiceTest extends TestCase
{
    /**
     * @var Client&MockObject
     */
    private $client;

    private string $indexName;

    private string $documentType;

    private ElasticSearchOrganizerSearchService $service;

    private ElasticSearchOrganizerSearchService $es8Service;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = 'udb3-core';
        $this->documentType = 'organizer';

        $pagedResultSetFactory = new ElasticSearchPagedResultSetFactory(
            new NullAggregationTransformer()
        );
        $pagedResultSetFactory->enableElasticSearch5CompatibilityMode();

        $this->service = new ElasticSearchOrganizerSearchService(
            $this->client,
            $this->indexName,
            $this->documentType,
            $pagedResultSetFactory
        );
        $this->service->enableElasticSearch5CompatibilityMode();

        $this->es8Service = new ElasticSearchOrganizerSearchService(
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
    public function it_injects_a_lowercase_type_filter_and_omits_the_type_parameter_for_es8(): void
    {
        $queryBuilder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStartAndLimit(new Start(0), new Limit(30));

        $id = '351b85c1-66ea-463b-82a6-515b7de0d267';
        $source = ['@id' => 'http://foo.bar/organizers/' . $id, '@type' => 'Organizer', 'originalEncodedJsonLd' => '{}'];

        $response = [
            'hits' => [
                'total' => ['value' => 1, 'relation' => 'eq'],
                'hits' => [['_index' => $this->indexName, '_id' => $id, '_source' => $source]],
            ],
        ];

        $this->client->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => $this->indexName,
                    'body' => [
                        '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
                        'from' => 0,
                        'size' => 30,
                        'query' => [
                            'bool' => [
                                'must' => [['match_all' => (object) []]],
                                'filter' => [
                                    ['term' => ['@type' => 'organizer']],
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->willReturn($response);

        $actualPagedResultSet = $this->es8Service->search($queryBuilder);

        $this->assertEquals(1, $actualPagedResultSet->getTotal());
    }

    /**
     * @test
     */
    public function it_returns_a_paged_result_set_for_the_given_search_query(): void
    {
        $queryBuilder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStartAndLimit(new Start(960), new Limit(30))
            ->withAutoCompleteFilter('Collectief');

        $idCollectiefCursief = '351b85c1-66ea-463b-82a6-515b7de0d267';

        $sourceCollectiefCursief = [
            '@id' => 'http://foo.bar/organizers/351b85c1-66ea-463b-82a6-515b7de0d267',
            '@type' => 'Organizer',
            'originalEncodedJsonLd' => '{}',
        ];

        $idCollectiefAC = 'bdc0f4ce-a211-463e-a8d1-d8b699fb1159';

        $sourceAC = [
            '@id' => 'http://foo.bar/organizers/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
            '@type' => 'Organizer',
            'originalEncodedJsonLd' => '{}',
        ];

        $response = [
            'hits' => [
                'total' => 962,
                'hits' => [
                    [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => $idCollectiefCursief,
                        '_source' => $sourceCollectiefCursief,
                    ],
                    [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => $idCollectiefAC,
                        '_source' => $sourceAC,
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
                                                'query' => 'Collectief',
                                            ],
                                        ],
                                    ],
                                ],
                                'should' => [
                                    [
                                        'match_phrase' => [
                                            'name.nl.autocomplete' => [
                                                'query' => 'Collectief',
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
            962,
            30,
            $expectedResults
        );

        $actualPagedResultSet = $this->service->search($queryBuilder);

        $this->assertEquals($expectedPagedResultSet, $actualPagedResultSet);
    }
}

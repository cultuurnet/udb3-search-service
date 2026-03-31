<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\Limit;
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

    private ElasticSearchOrganizerSearchService $service;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new ElasticSearchOrganizerSearchService(
            $this->client,
            'udb3-core',
            'organizer',
            new ElasticSearchPagedResultSetFactory(new NullAggregationTransformer())
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
                'hits' => [['_index' => 'udb3-core', '_id' => $id, '_source' => $source]],
            ],
        ];

        $this->client->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => 'udb3-core',
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

        $actualPagedResultSet = $this->service->search($queryBuilder);

        $this->assertEquals(1, $actualPagedResultSet->getTotal());
    }
}

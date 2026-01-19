<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\ElasticSearch\MocksElasticsearchResponse;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Psr\Log\LoggerInterface;

final class GetIndexNamesFromAliasTest extends AbstractOperationTestCase
{
    use MocksElasticsearchResponse;
    protected function createOperation(ElasticSearchClientInterface $client, LoggerInterface $logger): GetIndexNamesFromAlias
    {
        return new GetIndexNamesFromAlias($client, $logger);
    }

    /**
     * @test
     */
    public function it_returns_all_index_names_returned_by_elastic_search_for_a_given_alias(): void
    {
        $aliasName = 'udb3_core_read';

        $expectedNames = ['udb3_core_v20170328134523', 'udb3_core_v20170512112345'];

        $mockResponseData = [
            'udb3_core_v20170328134523' => [
                'aliases' => [
                    'udb3_core_read' => (object) [],
                    'udb3_core_write' => (object) [],
                ],
                'settings' => [
                    'index' => [
                        'number_of_shards' => 5,
                        'provided_name' => 'udb3_core_v2',
                        'creation_date' => '1494583641407',
                        'number_of_replicas' => '1',
                        'uuid' => '_SwFvhnQTGWhMwXXvWIJHQ',
                        'version' => [
                            'created' => '5030099',
                        ],
                    ],
                ],
            ],
            'udb3_core_v20170512112345' => [
                'aliases' => [
                    'udb3_core_read' => (object) [],
                    'udb3_core_write' => (object) [],
                ],
                'settings' => [
                    'index' => [
                        'number_of_shards' => 5,
                        'provided_name' => 'udb3_core_v2',
                        'creation_date' => '1494583641407',
                        'number_of_replicas' => '1',
                        'uuid' => '_SwFvhnQTGWhMwXXvWIJHQ',
                        'version' => [
                            'created' => '5030099',
                        ],
                    ],
                ],
            ],
        ];

        $this->indices->expects($this->once())
            ->method('get')
            ->with(['index' => $aliasName])
            ->willReturn($this->createElasticsearchResponse($mockResponseData));

        $actualNames = $this->operation->run($aliasName);

        $this->assertEquals($expectedNames, $actualNames);
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_if_the_alias_does_not_exist_or_another_error_occurred(): void
    {
        $aliasName = 'foo_bar';

        $expectedNames = [];

        $this->indices->expects($this->once())
            ->method('get')
            ->with(['index' => $aliasName])
            ->willThrowException(new ClientResponseException());

        $actualNames = $this->operation->run($aliasName);

        $this->assertEquals($expectedNames, $actualNames);
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;

class GetIndexNamesFromAliasTest extends AbstractOperationTestCase
{
    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @return GetIndexNamesFromAlias
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new GetIndexNamesFromAlias($client, $logger);
    }

    /**
     * @test
     */
    public function it_returns_all_index_names_returned_by_elastic_search_for_a_given_alias()
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
                        "version" => [
                            "created" => "5030099",
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
                        "version" => [
                            "created" => "5030099",
                        ],
                    ],
                ],
            ],
        ];

        $this->indices->expects($this->once())
            ->method('get')
            ->with(['index' => $aliasName])
            ->willReturn($mockResponseData);

        $actualNames = $this->operation->run($aliasName);

        $this->assertEquals($expectedNames, $actualNames);
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_if_the_alias_does_not_exist_or_another_error_occurred()
    {
        $aliasName = 'foo_bar';

        $expectedNames = [];

        $this->indices->expects($this->once())
            ->method('get')
            ->with(['index' => $aliasName])
            ->willThrowException(new Missing404Exception());

        $actualNames = $this->operation->run($aliasName);

        $this->assertEquals($expectedNames, $actualNames);
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class UpdateIndexAliasTest extends AbstractOperationTestCase
{
    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @return UpdateIndexAlias
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new UpdateIndexAlias($client, $logger);
    }

    /**
     * @test
     */
    public function it_adds_the_alias_to_the_new_index()
    {
        $newIndex = 'udb3_core_v1';
        $alias = 'udb3_core_write';

        $this->indices->expects($this->once())
            ->method('existsAlias')
            ->with(
                [
                    'name' => $alias,
                ]
            )
            ->willReturn(false);

        $this->indices->expects($this->once())
            ->method('putAlias')
            ->with(
                [
                    'index' => $newIndex,
                    'name' => $alias,
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Created alias udb3_core_write on index udb3_core_v1.'
            );

        $this->operation->run($alias, $newIndex);
    }

    /**
     * @test
     */
    public function it_deletes_the_alias_from_all_indexes_and_adds_it_to_the_new_index()
    {
        $oldIndex = 'udb3_core_v1';
        $newIndex = 'udb3_core_v2';
        $alias = 'udb3_core_write';

        $this->indices->expects($this->once())
            ->method('existsAlias')
            ->with(
                [
                    'name' => $alias,
                ]
            )
            ->willReturn(true);

        $this->indices->expects($this->once())
            ->method('getAlias')
            ->with(
                [
                    'name' => $alias,
                ]
            )
            ->willReturn(
                [
                    $oldIndex => [
                        'aliases' => [
                            $alias => [],
                        ],
                    ],
                    $newIndex => [
                        'aliases' => [
                            $alias => [],
                        ],
                    ],
                ]
            );

        $this->indices->expects($this->exactly(2))
            ->method('deleteAlias')
            ->withConsecutive(
                [
                    [
                        'index' => $oldIndex,
                        'name' => $alias,
                    ],
                ],
                [
                    [
                        'index' => $newIndex,
                        'name' => $alias,
                    ],
                ]
            );

        $this->indices->expects($this->once())
            ->method('putAlias')
            ->with(
                [
                    'index' => $newIndex,
                    'name' => $alias,
                ]
            );

        $this->logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    "Deleted alias udb3_core_write from index udb3_core_v1.",
                ],
                [
                    "Deleted alias udb3_core_write from index udb3_core_v2.",
                ],
                [
                    "Created alias udb3_core_write on index udb3_core_v2.",
                ]
            );

        $this->operation->run($alias, $newIndex);
    }
}

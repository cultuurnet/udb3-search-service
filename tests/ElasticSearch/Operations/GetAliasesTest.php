<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class GetAliasesTest extends AbstractOperationTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): GetAliases
    {
        return new GetAliases($client, $logger);
    }

    /**
     * @test
     */
    public function it_maps_each_alias_to_the_index_it_points_to(): void
    {
        $this->indices->expects($this->once())
            ->method('getAlias')
            ->with([])
            ->willReturn(
                [
                    'udb3_core_v20260714120000' => [
                        'aliases' => [
                            'udb3_core_read' => [],
                            'udb3_core_write' => [],
                        ],
                    ],
                    'geoshapes_v20250101000000' => [
                        'aliases' => [
                            'geoshapes_read' => [],
                            'geoshapes_write' => [],
                        ],
                    ],
                ]
            );

        $this->assertEquals(
            [
                'geoshapes_read' => 'geoshapes_v20250101000000',
                'geoshapes_write' => 'geoshapes_v20250101000000',
                'udb3_core_read' => 'udb3_core_v20260714120000',
                'udb3_core_write' => 'udb3_core_v20260714120000',
            ],
            $this->operation->run()
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_array_when_no_aliases_exist(): void
    {
        $this->indices->expects($this->once())
            ->method('getAlias')
            ->with([])
            ->willReturn([]);

        $this->assertEquals([], $this->operation->run());
    }
}

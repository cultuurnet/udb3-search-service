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

        $this->assertSame(
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
    public function it_skips_indices_without_aliases(): void
    {
        $this->indices->expects($this->once())
            ->method('getAlias')
            ->with([])
            ->willReturn(
                [
                    'udb3_core_v20260714120000' => [
                        'aliases' => [
                            'udb3_core_read' => [],
                        ],
                    ],
                    'udb3_core_v20250101000000' => [
                        'aliases' => [],
                    ],
                ]
            );

        $this->assertSame(
            [
                'udb3_core_read' => 'udb3_core_v20260714120000',
            ],
            $this->operation->run()
        );
    }

    /**
     * @test
     */
    public function it_ignores_dot_prefixed_system_indices(): void
    {
        $this->indices->expects($this->once())
            ->method('getAlias')
            ->with([])
            ->willReturn(
                [
                    'udb3_core_v20260714120000' => [
                        'aliases' => [
                            'udb3_core_read' => [],
                        ],
                    ],
                    '.kibana_7.17.x_001' => [
                        'aliases' => [
                            '.kibana' => [],
                        ],
                    ],
                ]
            );

        $this->assertSame(
            [
                'udb3_core_read' => 'udb3_core_v20260714120000',
            ],
            $this->operation->run()
        );
    }

    /**
     * @test
     */
    public function it_lets_the_last_index_in_iteration_order_win_when_an_alias_is_on_multiple_indices(): void
    {
        $this->indices->expects($this->once())
            ->method('getAlias')
            ->with([])
            ->willReturn(
                [
                    'udb3_core_v20250101000000' => [
                        'aliases' => [
                            'udb3_core_read' => [],
                        ],
                    ],
                    'udb3_core_v20260714120000' => [
                        'aliases' => [
                            'udb3_core_read' => [],
                        ],
                    ],
                ]
            );

        $this->assertSame(
            [
                'udb3_core_read' => 'udb3_core_v20260714120000',
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

        $this->assertSame([], $this->operation->run());
    }
}

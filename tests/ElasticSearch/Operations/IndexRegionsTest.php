<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

final class IndexRegionsTest extends AbstractOperationTestCase
{
    /**
     * @var Finder
     */
    private $finder;

    protected function setUp()
    {
        $this->finder = new Finder();
        parent::setUp();
    }

    /**
     * @return IndexRegions
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new IndexRegions($client, $logger, $this->finder);
    }

    /**
     * @test
     */
    public function it_indexes_all_files_located_in_the_given_path_or_subdirectories_that_match_the_file_name_regex()
    {
        $index = 'mock';
        $path = __DIR__ . '/data/regions/';

        $this->client->expects($this->exactly(3))
            ->method('index')
            ->withConsecutive(
                [
                    [
                        'index' => $index,
                        'id' => 'gem-antwerpen',
                        'type' => 'region',
                        'body' => json_decode(
                            file_get_contents(__DIR__ . '/data/regions/municipalities/gem-antwerpen.json'),
                            true
                        ),
                    ],
                ],
                [
                    [
                        'index' => $index,
                        'id' => 'gem-leuven',
                        'type' => 'region',
                        'body' => json_decode(
                            file_get_contents(__DIR__ . '/data/regions/municipalities/gem-leuven.json'),
                            true
                        ),
                    ],
                ],
                [
                    [
                        'index' => $index,
                        'id' => 'prov-vlaams-brabant',
                        'type' => 'region',
                        'body' => json_decode(
                            file_get_contents(__DIR__ . '/data/regions/provinces/prov-vlaams-brabant.json'),
                            true
                        ),
                    ],
                ]
            );

        $this->operation->run($index, $path);
    }
}

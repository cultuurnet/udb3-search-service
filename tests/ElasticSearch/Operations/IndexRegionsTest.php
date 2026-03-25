<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

final class IndexRegionsTest extends AbstractOperationTestCase
{
    private Finder $finder;

    protected function setUp(): void
    {
        $this->finder = new Finder();
        parent::setUp();
    }

    protected function createOperation(Client $client, LoggerInterface $logger): IndexRegions
    {
        return new IndexRegions($client, $logger, $this->finder);
    }

    private function createOperationForVersion(int $version): IndexRegions
    {
        $operation = new IndexRegions($this->client, $this->logger, new Finder());
        if ($version === 5) {
            $operation->enableType();
        }
        return $operation;
    }

    /**
     * @test
     */
    public function it_indexes_all_files_located_in_the_given_path_or_subdirectories_that_match_the_file_name_regex(): void
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
                        'body' => Json::decodeAssociatively(
                            FileReader::read(__DIR__ . '/data/regions/municipalities/gem-antwerpen.json')
                        ),
                    ],
                ],
                [
                    [
                        'index' => $index,
                        'id' => 'gem-leuven',
                        'type' => 'region',
                        'body' => Json::decodeAssociatively(
                            FileReader::read(__DIR__ . '/data/regions/municipalities/gem-leuven.json')
                        ),
                    ],
                ],
                [
                    [
                        'index' => $index,
                        'id' => 'prov-vlaams-brabant',
                        'type' => 'region',
                        'body' => Json::decodeAssociatively(
                            FileReader::read(__DIR__ . '/data/regions/provinces/prov-vlaams-brabant.json')
                        ),
                    ],
                ]
            );

        $this->operation->enableType();
        $this->operation->run($index, $path);
    }

    /**
     * @test
     */
    public function it_indexes_all_files_without_type_on_es8(): void
    {
        $index = 'mock';
        $path = __DIR__ . '/data/regions/';

        $operation = $this->createOperationForVersion(8);

        $this->client->expects($this->exactly(3))
            ->method('index')
            ->withConsecutive(
                [
                    [
                        'index' => $index,
                        'id' => 'gem-antwerpen',
                        'body' => Json::decodeAssociatively(
                            FileReader::read(__DIR__ . '/data/regions/municipalities/gem-antwerpen.json')
                        ),
                    ],
                ],
                [
                    [
                        'index' => $index,
                        'id' => 'gem-leuven',
                        'body' => Json::decodeAssociatively(
                            FileReader::read(__DIR__ . '/data/regions/municipalities/gem-leuven.json')
                        ),
                    ],
                ],
                [
                    [
                        'index' => $index,
                        'id' => 'prov-vlaams-brabant',
                        'body' => Json::decodeAssociatively(
                            FileReader::read(__DIR__ . '/data/regions/provinces/prov-vlaams-brabant.json')
                        ),
                    ],
                ]
            );

        $operation->run($index, $path);
    }
}

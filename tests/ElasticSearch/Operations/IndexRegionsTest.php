<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
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

    protected function createOperation(ElasticSearchClientInterface $client, LoggerInterface $logger): IndexRegions
    {
        return new IndexRegions($client, $logger, $this->finder);
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

        $this->operation->run($index, $path);
    }
}

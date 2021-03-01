<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class IndexRegions extends AbstractElasticSearchOperation
{
    /**
     * @var Finder
     */
    private $finder;


    public function __construct(
        Client $client,
        LoggerInterface $logger,
        Finder $finder
    ) {
        parent::__construct($client, $logger);
        $this->finder = $finder;
    }

    /**
     * @param string $indexName
     * @param string $pathToScan
     *   Path to scan recursively.
     * @param string $fileNameRegex
     *   File name (regex) to match.
     */
    public function run($indexName, $pathToScan, $fileNameRegex = '*.json')
    {
        $files = $this->finder
            ->files()
            ->name($fileNameRegex)
            ->in($pathToScan)
            ->sortByName();

        /* @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $id = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $json = $file->getContents();

            $this->logger->info("Indexing region {$id}...");

            $this->client->index(
                [
                    'index' => $indexName,
                    'type' => 'region',
                    'id' => $id,
                    'body' => json_decode($json, true),
                ]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\Json;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class IndexRegions extends AbstractElasticSearchOperation
{
    private Finder $finder;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        Finder $finder
    ) {
        parent::__construct($client, $logger);
        $this->finder = $finder;
    }

    public function run(string $indexName, string $pathToScan, string $fileNameRegex = '*.json'): void
    {
        $files = $this->finder
            ->files()
            ->name($fileNameRegex)
            ->in($pathToScan)
            ->sortByName();

        /* @var SplFileInfo $file */
        foreach ($files as $file) {
            $id = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $json = $file->getContents();

            $this->logger->info("Indexing region {$id}...");

            $this->client->index(
                [
                    'index' => $indexName,
                    'type' => 'region',
                    'id' => $id,
                    'body' => Json::decodeAssociatively($json),
                ]
            );
        }
    }
}

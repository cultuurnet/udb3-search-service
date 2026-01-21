<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Psr\Log\LoggerInterface;

final class SingleFileIndexationStrategy implements IndexationStrategy
{
    private ElasticSearchClientInterface $elasticSearchClient;

    private LoggerInterface $logger;


    public function __construct(
        ElasticSearchClientInterface $elasticSearchClient,
        LoggerInterface $logger
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->logger = $logger;
    }


    public function indexDocument(
        string $indexName,
        string $documentType,
        JsonDocument $jsonDocument
    ): void {
        $id = $jsonDocument->getId();

        $this->logger->info("Sending document {$id} to ElasticSearch...");

        $this->elasticSearchClient->index(
            [
                'index' => $indexName,
                'type' => $documentType,
                'id' => $id,
                'body' => (array) $jsonDocument->getBody(),
            ]
        );
    }

    public function finish(): void
    {
    }
}

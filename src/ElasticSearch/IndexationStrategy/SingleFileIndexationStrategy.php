<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;

final class SingleFileIndexationStrategy implements IndexationStrategy
{
    private ClientInterface $elasticSearchClient;

    private LoggerInterface $logger;


    public function __construct(
        ClientInterface $elasticSearchClient,
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

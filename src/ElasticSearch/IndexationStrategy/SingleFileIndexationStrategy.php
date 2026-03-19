<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class SingleFileIndexationStrategy implements IndexationStrategy
{
    private Client $elasticSearchClient;

    private LoggerInterface $logger;

    private int $elasticsearchVersion;


    public function __construct(
        Client $elasticSearchClient,
        LoggerInterface $logger,
        int $elasticsearchVersion = 5
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->logger = $logger;
        $this->elasticsearchVersion = $elasticsearchVersion;
    }


    public function indexDocument(
        string $indexName,
        string $documentType,
        JsonDocument $jsonDocument
    ): void {
        $id = $jsonDocument->getId();

        $this->logger->info("Sending document {$id} to ElasticSearch...");

        $params = [
            'index' => $indexName,
            'id' => $id,
            'body' => (array) $jsonDocument->getBody(),
        ];

        if ($this->elasticsearchVersion !== 8) {
            $params['type'] = $documentType;
        }

        $this->elasticSearchClient->index($params);
    }

    public function finish(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

final class SingleFileIndexationStrategy implements IndexationStrategyInterface
{
    /**
     * @var Client
     */
    private $elasticSearchClient;

    /**
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(
        Client $elasticSearchClient,
        LoggerInterface $logger
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->logger = $logger;
    }


    public function indexDocument(
        StringLiteral $indexName,
        StringLiteral $documentType,
        JsonDocument $jsonDocument
    ) {
        $id = $jsonDocument->getId();

        $this->logger->info("Sending document {$id} to ElasticSearch...");

        $this->elasticSearchClient->index(
            [
                'index' => $indexName->toNative(),
                'type' => $documentType->toNative(),
                'id' => $id,
                'body' => (array) $jsonDocument->getBody(),
            ]
        );
    }

    public function finish(): void
    {
    }
}

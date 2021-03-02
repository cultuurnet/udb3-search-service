<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

final class BulkIndexationStrategy implements BulkIndexationStrategyInterface
{
    /**
     * @var Client
     */
    private $elasticSearchClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $autoFlushThreshold;

    /**
     * @var JsonDocument[]
     */
    private $queuedDocuments;

    /**
     * @param int $autoFlushThreshold
     */
    public function __construct(
        Client $elasticSearchClient,
        LoggerInterface $logger,
        $autoFlushThreshold
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->logger = $logger;
        $this->autoFlushThreshold = $autoFlushThreshold;

        $this->queuedDocuments = [];
    }


    public function indexDocument(
        StringLiteral $indexName,
        StringLiteral $documentType,
        JsonDocument $jsonDocument
    ) {
        $id = $jsonDocument->getId();
        $this->logger->info("Queuing document {$id} for indexation.");

        $this->queuedDocuments[] = [
            'index' => $indexName->toNative(),
            'type' => $documentType->toNative(),
            'id' => $jsonDocument->getId(),
            'body' => json_decode($jsonDocument->getRawBody(), true),
        ];

        $this->autoFlush();
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_indexing_documents.html#_bulk_indexing
     */
    public function flush(): void
    {
        $count = count($this->queuedDocuments);
        $this->logger->info("Sending {$count} documents to ElasticSearch for indexation...");

        $parameters = [];

        foreach ($this->queuedDocuments as $queuedDocument) {
            $parameters['body'][] = [
                'index' => [
                    '_index' => $queuedDocument['index'],
                    '_type' => $queuedDocument['type'],
                    '_id' => $queuedDocument['id'],
                ],
            ];

            $parameters['body'][] = $queuedDocument['body'];
        }

        if (!empty($parameters)) {
            $this->elasticSearchClient->bulk($parameters);
        }

        $this->logger->info('Bulk indexation completed.');

        $this->queuedDocuments = [];
    }

    private function autoFlush()
    {
        if (count($this->queuedDocuments) >= $this->autoFlushThreshold) {
            $this->flush();
        }
    }
}

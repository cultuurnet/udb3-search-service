<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;

final class BulkIndexationStrategy implements IndexationStrategy
{
    private ClientInterface $elasticSearchClient;

    private LoggerInterface $logger;

    private int $autoFlushThreshold;

    private array $queuedDocuments;

    public function __construct(
        ClientInterface $elasticSearchClient,
        LoggerInterface $logger,
        int $autoFlushThreshold
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->logger = $logger;
        $this->autoFlushThreshold = $autoFlushThreshold;

        $this->queuedDocuments = [];
    }

    public function indexDocument(
        string $indexName,
        string $documentType,
        JsonDocument $jsonDocument
    ): void {
        $id = $jsonDocument->getId();
        $this->logger->info("Queuing document {$id} for indexation.");

        $this->queuedDocuments[] = [
            'index' => $indexName,
            'type' => $documentType,
            'id' => $jsonDocument->getId(),
            'body' => Json::decodeAssociatively($jsonDocument->getRawBody()),
        ];

        $this->autoFlush();
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_indexing_documents.html#_bulk_indexing
     */
    public function finish(): void
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

    private function autoFlush(): void
    {
        if (count($this->queuedDocuments) >= $this->autoFlushThreshold) {
            $this->finish();
        }
    }
}

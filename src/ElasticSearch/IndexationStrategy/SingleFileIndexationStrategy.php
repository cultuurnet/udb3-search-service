<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearch5Compatibility;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentCouldNotBeIndexed;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Psr\Log\LoggerInterface;

final class SingleFileIndexationStrategy implements IndexationStrategy
{
    use ElasticSearch5Compatibility;

    private Client $elasticSearchClient;

    private LoggerInterface $logger;


    public function __construct(
        Client $elasticSearchClient,
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

        $params = [
            'index' => $indexName,
            'id' => $id,
            'body' => (array) $jsonDocument->getBody(),
        ];

        if ($this->usesDocumentTypes()) {
            $params['type'] = $documentType;
        }

        try {
            $this->elasticSearchClient->index($params);
        } catch (ElasticsearchException $e) {
            throw new ElasticSearchDocumentCouldNotBeIndexed(
                sprintf(
                    'ElasticSearch index request failed (id: %s, index: %s, body size: %d bytes).',
                    $id,
                    $indexName,
                    strlen($jsonDocument->getRawBody())
                ),
                0,
                $e
            );
        }
    }

    public function finish(): void
    {
    }
}

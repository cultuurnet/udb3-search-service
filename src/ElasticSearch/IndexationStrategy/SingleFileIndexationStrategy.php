<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearch5Compatibility;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentCouldNotBeIndexed;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Throwable;

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
        } catch (Throwable $e) {
            // The ElasticSearch exception message doesn't always contain the document id, so we rethrow
            // with the id baked in. Log context is dropped before Sentry, so the id must live in the message.
            throw ElasticSearchDocumentCouldNotBeIndexed::forDocument($id, $e);
        }
    }

    public function finish(): void
    {
    }
}

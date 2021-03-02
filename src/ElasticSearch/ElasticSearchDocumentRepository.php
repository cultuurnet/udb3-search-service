<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\IndexationStrategyInterface;
use CultuurNet\UDB3\Search\ReadModel\DocumentGone;
use CultuurNet\UDB3\Search\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchDocumentRepository implements DocumentRepository
{
    use HasElasticSearchClient;

    /**
     * @var IndexationStrategyInterface
     */
    private $indexationStrategy;

    public function __construct(
        Client $elasticSearchClient,
        StringLiteral $indexName,
        StringLiteral $documentType,
        IndexationStrategyInterface $indexationStrategy
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->indexName = $indexName;
        $this->documentType = $documentType;
        $this->indexationStrategy = $indexationStrategy;
    }

    public function get(string $id): ?JsonDocument
    {
        $response = $this->elasticSearchClient->get(
            $this->createParameters(['id' => $id])
        );

        $found = isset($response['found']) && $response['found'] == true;
        $version = isset($response['_version']) ? (int) $response['_version'] : 0;

        if (!$found) {
            if ($version > 0) {
                throw new DocumentGone();
            } else {
                return null;
            }
        }

        return (new JsonDocument($id))
            ->withBody($response['_source']);
    }

    public function save(JsonDocument $readModel): void
    {
        $this->indexationStrategy->indexDocument($this->indexName, $this->documentType, $readModel);
    }

    public function remove(string $id): void
    {
        $this->elasticSearchClient->delete(
            $this->createParameters(['id' => $id])
        );
    }

    public function getDocumentType(): string
    {
        return $this->documentType->toNative();
    }
}

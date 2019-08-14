<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\IndexationStrategyInterface;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchDocumentRepository implements DocumentRepositoryInterface
{
    use HasElasticSearchClient;

    /**
     * @var IndexationStrategyInterface
     */
    private $indexationStrategy;

    /**
     * @param Client $elasticSearchClient
     * @param StringLiteral $indexName
     * @param StringLiteral $documentType
     * @param IndexationStrategyInterface $indexationStrategy
     */
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

    /**
     * @param string $id
     * @return JsonDocument
     *
     * @throws DocumentGoneException
     */
    public function get($id)
    {
        $response = $this->elasticSearchClient->get(
            $this->createParameters(['id' => $id])
        );

        $found = isset($response['found']) && $response['found'] == true;
        $version = isset($response['_version']) ? (int) $response['_version'] : 0;

        if (!$found) {
            if ($version > 0) {
                throw new DocumentGoneException();
            } else {
                return null;
            }
        }

        return (new JsonDocument($id))
            ->withBody($response['_source']);
    }

    /**
     * @param JsonDocument $readModel
     */
    public function save(JsonDocument $readModel)
    {
        $this->indexationStrategy->indexDocument($this->indexName, $this->documentType, $readModel);
    }

    /**
     * @param string $id
     */
    public function remove($id)
    {
        $this->elasticSearchClient->delete(
            $this->createParameters(['id' => $id])
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elasticsearch\Client;

trait HasElasticSearchClient
{
    /**
     * @var Client
     */
    private $elasticSearchClient;

    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $documentType;

    /**
     * @return array
     */
    private function getDefaultParameters()
    {
        return [
            'index' => $this->indexName,
            'type' => $this->documentType,
        ];
    }

    /**
     * @return array
     */
    private function executeQuery(array $body, array $parameters = [])
    {
        $parameters['body'] = $body;

        return $this->elasticSearchClient->search(
            $this->createParameters($parameters)
        );
    }

    /**
     * @return array
     */
    private function createParameters(array $parameters)
    {
        return $this->getDefaultParameters() + $parameters;
    }
}

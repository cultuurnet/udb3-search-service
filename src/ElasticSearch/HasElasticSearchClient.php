<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elasticsearch\Client;

trait HasElasticSearchClient
{
    private Client $elasticSearchClient;

    private string $indexName;

    private string $documentType;

    private int $elasticsearchVersion = 5;

    private function getDefaultParameters(): array
    {
        $params = ['index' => $this->indexName];

        if ($this->elasticsearchVersion !== 8) {
            $params['type'] = $this->documentType;
        }

        return $params;
    }

    private function executeQuery(array $body, array $parameters = []): array
    {
        if ($this->elasticsearchVersion === 8) {
            if (!isset($body['query']['bool'])) {
                $body['query'] = ['bool' => ['must' => [$body['query']]]];
            }
            $body['query']['bool']['filter'][] = ['term' => ['@type' => strtolower($this->documentType)]];
        }

        $parameters['body'] = $body;

        return $this->elasticSearchClient->search(
            $this->createParameters($parameters)
        );
    }

    private function createParameters(array $parameters): array
    {
        return $this->getDefaultParameters() + $parameters;
    }
}

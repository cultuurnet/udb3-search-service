<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elasticsearch\Client;

trait HasElasticSearchClient
{
    private Client $elasticSearchClient;

    private string $indexName;

    private string $documentType;

    private function getDefaultParameters(): array
    {
        return ['index' => $this->indexName];
    }

    private function executeQuery(array $body, array $parameters = []): array
    {
        if (!isset($body['query']['bool'])) {
            $body['query'] = ['bool' => ['must' => [$body['query']]]];
        }
        $types = array_map('strtolower', explode(',', $this->documentType));
        $body['query']['bool']['filter'][] = count($types) === 1
            ? ['term' => ['@type' => $types[0]]]
            : ['terms' => ['@type' => $types]];

        $body['track_total_hits'] = true;
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

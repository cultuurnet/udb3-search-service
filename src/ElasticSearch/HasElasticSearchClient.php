<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elasticsearch\Client;

trait HasElasticSearchClient
{
    use ElasticSearch5Compatibility;

    private Client $elasticSearchClient;

    private string $indexName;

    private string $documentType;

    private function getDefaultParameters(): array
    {
        $params = ['index' => $this->indexName];

        if ($this->usesDocumentTypes()) {
            $params['type'] = $this->documentType;
        }

        return $params;
    }

    private function executeQuery(array $body, array $parameters = []): array
    {
        if (!$this->usesDocumentTypes()) {
            if (!isset($body['query']['bool'])) {
                $body['query'] = ['bool' => ['must' => [$body['query']]]];
            }
            $types = array_map('strtolower', explode(',', $this->documentType));
            $body['query']['bool']['filter'][] = count($types) === 1
                ? ['term' => ['@type' => $types[0]]]
                : ['terms' => ['@type' => $types]];
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

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

trait HasElasticSearchClient
{
    private ElasticSearchClientInterface $elasticSearchClient;

    private string $indexName;

    private string $documentType;

    private function getDefaultParameters(): array
    {
        return [
            'index' => $this->indexName,
            'type' => $this->documentType,
        ];
    }

    private function executeQuery(array $body, array $parameters = []): array
    {
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

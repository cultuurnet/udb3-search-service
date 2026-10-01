<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\Endpoints\Indices;

final class ElasticsearchPhpIndices implements ElasticSearchIndices
{
    use SendsRequests;

    public function __construct(private readonly Indices $indices)
    {
    }

    public function exists(array $params): bool
    {
        return $this->send(fn () => $this->indices->exists($params))->asBool();
    }

    public function existsAlias(array $params): bool
    {
        return $this->send(fn () => $this->indices->existsAlias($params))->asBool();
    }

    public function get(array $params): array
    {
        return $this->send(fn () => $this->indices->get($params))->asArray();
    }

    public function getAlias(array $params): array
    {
        return $this->send(fn () => $this->indices->getAlias($params))->asArray();
    }

    public function putAlias(array $params): void
    {
        $this->send(fn () => $this->indices->putAlias($params));
    }

    public function deleteAlias(array $params): void
    {
        $this->send(fn () => $this->indices->deleteAlias($params));
    }

    public function create(array $params): void
    {
        $this->send(fn () => $this->indices->create($params));
    }

    public function delete(array $params): void
    {
        $this->send(fn () => $this->indices->delete($params));
    }

    public function putMapping(array $params): void
    {
        $this->send(fn () => $this->indices->putMapping($params));
    }

    public function putTemplate(array $params): void
    {
        $this->send(fn () => $this->indices->putTemplate($params));
    }
}

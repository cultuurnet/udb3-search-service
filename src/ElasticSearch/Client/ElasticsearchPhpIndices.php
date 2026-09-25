<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

final class ElasticsearchPhpIndices implements ElasticSearchIndices
{
    public function __construct(private readonly Indices $indices)
    {
    }

    public function exists(array $params): bool
    {
        return $this->sync($this->indices->exists($params))->asBool();
    }

    public function existsAlias(array $params): bool
    {
        return $this->sync($this->indices->existsAlias($params))->asBool();
    }

    public function get(array $params): array
    {
        return $this->sync($this->indices->get($params))->asArray();
    }

    public function getAlias(array $params): array
    {
        return $this->sync($this->indices->getAlias($params))->asArray();
    }

    public function putAlias(array $params): void
    {
        $this->indices->putAlias($params);
    }

    public function deleteAlias(array $params): void
    {
        $this->indices->deleteAlias($params);
    }

    public function create(array $params): void
    {
        $this->indices->create($params);
    }

    public function delete(array $params): void
    {
        $this->indices->delete($params);
    }

    public function putMapping(array $params): void
    {
        $this->indices->putMapping($params);
    }

    public function putTemplate(array $params): void
    {
        $this->indices->putTemplate($params);
    }

    /**
     * The client only returns a Promise in async mode, which this service never enables.
     */
    private function sync(Elasticsearch|Promise $response): Elasticsearch
    {
        assert($response instanceof Elasticsearch);
        return $response;
    }
}

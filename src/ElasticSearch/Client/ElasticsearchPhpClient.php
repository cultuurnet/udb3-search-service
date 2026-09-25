<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

final class ElasticsearchPhpClient implements ElasticSearchClient
{
    public function __construct(private readonly Client $client)
    {
    }

    public function search(array $params): array
    {
        return $this->sync($this->client->search($params))->asArray();
    }

    public function scroll(array $params): array
    {
        return $this->sync($this->client->scroll($params))->asArray();
    }

    public function clearScroll(array $params): void
    {
        $this->client->clearScroll($params);
    }

    public function get(array $params): array
    {
        return $this->sync($this->client->get($params))->asArray();
    }

    public function index(array $params): void
    {
        $this->client->index($params);
    }

    public function delete(array $params): void
    {
        $this->client->delete($params);
    }

    public function bulk(array $params): void
    {
        $this->client->bulk($params);
    }

    public function indices(): ElasticSearchIndices
    {
        return new ElasticsearchPhpIndices($this->client->indices());
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

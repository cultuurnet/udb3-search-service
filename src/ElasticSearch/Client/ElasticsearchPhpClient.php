<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\Client;

final class ElasticsearchPhpClient implements ElasticSearchClient
{
    use SendsRequests;

    public function __construct(private readonly Client $client)
    {
    }

    public function search(array $params): array
    {
        return $this->send(fn () => $this->client->search($params))->asArray();
    }

    public function scroll(array $params): array
    {
        return $this->send(fn () => $this->client->scroll($params))->asArray();
    }

    public function clearScroll(array $params): void
    {
        $this->send(fn () => $this->client->clearScroll($params));
    }

    public function get(array $params): array
    {
        return $this->send(fn () => $this->client->get($params))->asArray();
    }

    public function index(array $params): void
    {
        $this->send(fn () => $this->client->index($params));
    }

    public function delete(array $params): void
    {
        $this->send(fn () => $this->client->delete($params));
    }

    public function bulk(array $params): void
    {
        $this->send(fn () => $this->client->bulk($params));
    }

    public function indices(): ElasticSearchIndices
    {
        return new ElasticsearchPhpIndices($this->client->indices());
    }
}

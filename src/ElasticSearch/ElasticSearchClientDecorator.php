<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchAlias;
use Elastic\Elasticsearch\Traits\ClientEndpointsTrait;
use Elastic\Elasticsearch\Traits\EndpointTrait;
use Elastic\Elasticsearch\Utility;
use Elastic\Transport\Transport;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

final class ElasticSearchClientDecorator implements ElasticSearchClientInterface
{
    use ClientEndpointsTrait;
    use EndpointTrait;

    public function __construct(private readonly Client $client)
    {
    }

    public function sendRequest(RequestInterface $request): ElasticsearchAlias
    {
        $request = $this->client->sendRequest($request);

        if ($request instanceof Promise) {
            throw new \DomainException('Async return not supported for Elastic Search');
        }

        return $request;
    }

    public function indices(): Indices
    {
        return $this->client->indices();
    }

    public function getTransport(): Transport
    {
        return $this->client->getTransport();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->client->getLogger();
    }

    public function setAsync(bool $async): \Elastic\Elasticsearch\ClientInterface
    {
        $this->client->setAsync($async);
        return $this;
    }

    public function getAsync(): bool
    {
        return $this->client->getAsync();
    }

    public function setElasticMetaHeader(bool $active): \Elastic\Elasticsearch\ClientInterface
    {
        return $this->client->setElasticMetaHeader($active);
    }

    public function getElasticMetaHeader(): bool
    {
        return $this->client->getElasticMetaHeader();
    }

    public function setResponseException(bool $active): \Elastic\Elasticsearch\ClientInterface
    {
        $this->client->setResponseException($active);
        return $this;
    }

    public function getResponseException(): bool
    {
        return $this->client->getResponseException();
    }
}

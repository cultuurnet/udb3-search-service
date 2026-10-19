<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

trait SendsRequests
{
    /**
     * @param callable(): (Elasticsearch|Promise) $request
     */
    private function send(callable $request): Elasticsearch
    {
        try {
            $response = $request();
        } catch (ClientResponseException|ServerResponseException $exception) {
            throw ElasticSearchRequestFailed::fromResponseException($exception);
        }

        // The client only returns a Promise in async mode, which this service never enables.
        assert($response instanceof Elasticsearch);
        return $response;
    }
}

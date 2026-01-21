<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\Json;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elastic\Elasticsearch\Transport\AsyncOnSuccess;
use GuzzleHttp\Psr7\Response;

trait ElasticSearchResponseHelper
{
    protected function getElasticSearchResponse(int $status = 200, array $body = []): Elasticsearch
    {
        if (empty($body)) {
            $body = Json::encode(['ok' => $status === 200]);
        } else {
            $body = Json::encode($body);
        }

        $response = new Response(
            $status,
            ['Content-Type' => 'application/json', Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            $body
        );
        return (new AsyncOnSuccess())->success($response, 1);
    }

    protected function getElasticSearchResponseFromString(string $body): Elasticsearch
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json', Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            $body
        );
        return (new AsyncOnSuccess())->success($response, 1);
    }
}

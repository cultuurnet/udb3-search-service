<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elastic\Elasticsearch\Response\Elasticsearch;
use PHPUnit\Framework\MockObject\MockObject;

trait MocksElasticsearchResponse
{
    abstract protected function createMock(string $originalClassName): MockObject;

    /**
     * Create a mock Elasticsearch response that returns the given array when asArray() is called.
     * Use this for methods like search(), get(), scroll(), etc.
     */
    private function createElasticsearchResponse(array $data): Elasticsearch&MockObject
    {
        $response = $this->createMock(Elasticsearch::class);
        $response->method('asArray')->willReturn($data);
        return $response;
    }

    private function createElasticsearchResponseAsObject(array $data): Elasticsearch&MockObject
    {
        $response = $this->createMock(Elasticsearch::class);
        $response->method('asObject')->willReturn($data);
        return $response;
    }

    /**
     * Create a mock Elasticsearch response that returns a boolean when asBool() is called.
     * Use this for methods that return boolean results.
     */
    private function createElasticsearchBoolResponse(bool $result): Elasticsearch&MockObject
    {
        $response = $this->createMock(Elasticsearch::class);
        $response->method('asBool')->willReturn($result);
        return $response;
    }
}

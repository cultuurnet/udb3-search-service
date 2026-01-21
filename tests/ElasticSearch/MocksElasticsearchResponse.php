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
}

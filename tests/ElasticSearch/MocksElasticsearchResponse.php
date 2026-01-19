<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elastic\Elasticsearch\Response\Elasticsearch;
use PHPUnit\Framework\MockObject\MockObject;

trait MocksElasticsearchResponse
{
    abstract protected function createMock(string $originalClassName): MockObject;

    private function createElasticsearchResponse(array $data): Elasticsearch&MockObject
    {
        $response = $this->createMock(Elasticsearch::class);
        $response->method('asArray')->willReturn($data);
        return $response;
    }
}

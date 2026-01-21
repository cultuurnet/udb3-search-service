<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\Json;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elastic\Elasticsearch\Transport\AsyncOnSuccess;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

final class CheckIndexExistsTest extends AbstractOperationTestCase
{
    protected function createOperation(ElasticSearchClientInterface $client, LoggerInterface $logger): CheckIndexExists
    {
        return new CheckIndexExists($client, $logger);
    }

    /**
     * @test
     * @dataProvider indexExistsDataProvider
     *
     */
    public function it_returns_the_status_of_the_given_index_returned_by_the_api_client(
        string $indexName,
        bool $exists,
        string $log
    ): void {
        $response = new Response(
            $exists ? 200 : 301,
            ['Content-Type' => 'application/json', Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            Json::encode(['ok' => true])
        );
        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn((new AsyncOnSuccess)->success($response, 1));

        $this->logger->expects($this->once())
            ->method('info')
            ->with($log);

        $this->assertEquals($exists, $this->operation->run($indexName));
    }


    public function indexExistsDataProvider(): array
    {
        return [
            [
                'indexName' => 'acme',
                'exists' => true,
                'log' => 'Index acme exists.',
            ],
            [
                'indexName' => 'mock',
                'exists' => false,
                'log' => 'Index mock does not exist.',
            ],
        ];
    }
}

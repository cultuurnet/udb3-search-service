<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class CheckIndexExistsTest extends AbstractOperationTestCase
{
    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @return CheckIndexExists
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new CheckIndexExists($client, $logger);
    }

    /**
     * @test
     * @dataProvider indexExistsDataProvider
     *
     * @param string $indexName
     * @param bool $exists
     * @param string $log
     */
    public function it_returns_the_status_of_the_given_index_returned_by_the_api_client(
        $indexName,
        $exists,
        $log
    ) {
        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn($exists);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($log);

        $this->assertEquals($exists, $this->operation->run($indexName));
    }

    /**
     * @return array
     */
    public function indexExistsDataProvider()
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

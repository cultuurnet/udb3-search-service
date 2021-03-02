<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class DeleteIndexTest extends AbstractOperationTestCase
{
    /**
     * @return DeleteIndex
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new DeleteIndex($client, $logger);
    }

    /**
     * @test
     */
    public function it_deletes_the_index_if_it_exists()
    {
        $indexName = 'mock';

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn(true);

        $this->indices->expects($this->once())
            ->method('delete')
            ->with(['index' => $indexName]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Index mock was deleted.');

        $this->operation->run($indexName);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_index_does_not_exist()
    {
        $indexName = 'mock';

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Index mock does not exist.');

        $this->operation->run($indexName);
    }
}

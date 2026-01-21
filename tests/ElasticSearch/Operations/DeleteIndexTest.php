<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchResponseHelper;
use Psr\Log\LoggerInterface;

final class DeleteIndexTest extends AbstractOperationTestCase
{
    use ElasticSearchResponseHelper;

    protected function createOperation(ElasticSearchClientInterface $client, LoggerInterface $logger): DeleteIndex
    {
        return new DeleteIndex($client, $logger);
    }

    /**
     * @test
     */
    public function it_deletes_the_index_if_it_exists(): void
    {
        $indexName = 'mock';

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn($this->getElasticSearchResponse());

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
    public function it_does_nothing_if_the_index_does_not_exist(): void
    {
        $indexName = 'mock';

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn($this->getElasticSearchResponse(301));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Index mock does not exist.');

        $this->operation->run($indexName);
    }
}

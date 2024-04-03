<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class CreateIndexTest extends AbstractOperationTestCase
{
    /**
     * @return CreateIndex
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new CreateIndex($client, $logger);
    }

    /**
     * @test
     */
    public function it_creates_the_new_index_if_it_does_not_exist_yet(): void
    {
        $indexName = 'mock';
        $force = false;

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn(false);

        $this->indices->expects($this->once())
            ->method('create')
            ->with(['index' => $indexName]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Index mock created.');

        $this->operation->run($indexName, $force);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_index_already_exists_and_force_is_disabled(): void
    {
        $indexName = 'mock';
        $force = false;

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Index mock already exists!');

        $this->operation->run($indexName, $force);
    }

    /**
     * @test
     */
    public function it_overwrites_an_existing_index_if_force_is_enabled(): void
    {
        $indexName = 'mock';
        $force = true;

        $this->indices->expects($this->once())
            ->method('exists')
            ->with(['index' => $indexName])
            ->willReturn(true);

        $this->indices->expects($this->once())
            ->method('delete')
            ->with(['index' => $indexName]);

        $this->indices->expects($this->once())
            ->method('create')
            ->with(['index' => $indexName]);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Existing index mock deleted.'],
                ['Index mock created.']
            );

        $this->operation->run($indexName, $force);
    }
}

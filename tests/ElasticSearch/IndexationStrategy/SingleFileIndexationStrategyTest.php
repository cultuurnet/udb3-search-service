<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SingleFileIndexationStrategyTest extends TestCase
{
    private ElasticSearchClientInterface&MockObject $client;

    private string $indexName;

    private string $documentType;

    private LoggerInterface&MockObject $logger;

    private SingleFileIndexationStrategy $strategy;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ElasticSearchClientInterface::class);

        $this->indexName = 'udb3-core';
        $this->documentType = 'event';

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->strategy = new SingleFileIndexationStrategy(
            $this->client,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_sends_the_document_directly_to_elasticsearch_for_indexation(): void
    {
        $jsonDocument = new JsonDocument('cff29f09-5104-4f0d-85ca-8d6cdd28849b', '{"foo":"bar"}');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Sending document cff29f09-5104-4f0d-85ca-8d6cdd28849b to ElasticSearch...');

        $this->client->expects($this->once())
            ->method('index')
            ->with(
                [
                    'index' => $this->indexName,
                    'type' => $this->documentType,
                    'id' => 'cff29f09-5104-4f0d-85ca-8d6cdd28849b',
                    'body' => ['foo' => 'bar'],
                ]
            );

        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument);
    }
}

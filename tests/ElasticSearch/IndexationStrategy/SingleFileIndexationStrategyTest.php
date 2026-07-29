<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentCouldNotBeIndexed;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SingleFileIndexationStrategyTest extends TestCase
{
    /**
     * @var Client&MockObject
     */
    private $client;


    private string $indexName;


    private string $documentType;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;


    private SingleFileIndexationStrategy $strategy;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        $jsonDocument = new JsonDocument('cff29f09-5104-4f0d-85ca-8d6cdd28849b', '{"@type":"Event","foo":"bar"}');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Sending document cff29f09-5104-4f0d-85ca-8d6cdd28849b to ElasticSearch...');

        $this->client->expects($this->once())
            ->method('index')
            ->with(
                [
                    'index' => $this->indexName,
                    'id' => 'cff29f09-5104-4f0d-85ca-8d6cdd28849b',
                    'body' => ['@type' => 'Event', 'foo' => 'bar'],
                ]
            );

        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument);
    }

    /**
     * @test
     */
    public function it_wraps_elasticsearch_failures_with_the_document_id(): void
    {
        $jsonDocument = new JsonDocument('cff29f09-5104-4f0d-85ca-8d6cdd28849b', '{"@type":"Event","foo":"bar"}');

        $this->client->expects($this->once())
            ->method('index')
            ->willThrowException(new RuntimeException('nested documents limit exceeded'));

        try {
            $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument);
            $this->fail('Expected ' . ElasticSearchDocumentCouldNotBeIndexed::class . ' to be thrown.');
        } catch (ElasticSearchDocumentCouldNotBeIndexed $e) {
            $this->assertStringContainsString('cff29f09-5104-4f0d-85ca-8d6cdd28849b', $e->getMessage());
            $this->assertStringContainsString('nested documents limit exceeded', $e->getMessage());
        }
    }
}

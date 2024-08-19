<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Elasticsearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BulkIndexationStrategyTest extends TestCase
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


    private int $autoFlushThreshold;


    private BulkIndexationStrategy $strategy;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = 'udb3-core';
        $this->documentType = 'event';

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->autoFlushThreshold = 5;

        $this->strategy = new BulkIndexationStrategy(
            $this->client,
            $this->logger,
            $this->autoFlushThreshold
        );
    }

    /**
     * @test
     */
    public function it_queues_the_documents_and_indexes_them_in_bulk_when_the_auto_flush_threshold_has_been_reached(): void
    {
        $jsonDocument1 = new JsonDocument('cff29f09-5104-4f0d-85ca-8d6cdd28849b', '{"foo":"bar1"}');
        $jsonDocument2 = new JsonDocument('5cb3f31d-ffb4-4de5-86bd-852825d94ff2', '{"foo":"bar2"}');
        $jsonDocument3 = new JsonDocument('014aef8c-0b63-4775-9ac6-68d880a11fc7', '{"foo":"bar3"}');
        $jsonDocument4 = new JsonDocument('21dc5755-93c1-4443-9ee9-3ca0373a1107', '{"foo":"bar4"}');
        $jsonDocument5 = new JsonDocument('8d429d11-ffdb-4c59-a530-792c5bf028df', '{"foo":"bar5"}');

        $this->logger->expects($this->exactly(7))
            ->method('info')
            ->withConsecutive(
                ['Queuing document cff29f09-5104-4f0d-85ca-8d6cdd28849b for indexation.'],
                ['Queuing document 5cb3f31d-ffb4-4de5-86bd-852825d94ff2 for indexation.'],
                ['Queuing document 014aef8c-0b63-4775-9ac6-68d880a11fc7 for indexation.'],
                ['Queuing document 21dc5755-93c1-4443-9ee9-3ca0373a1107 for indexation.'],
                ['Queuing document 8d429d11-ffdb-4c59-a530-792c5bf028df for indexation.'],
                ['Sending 5 documents to ElasticSearch for indexation...'],
                ['Bulk indexation completed.']
            );

        $expectedParameters = [
            'body' => [
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => 'cff29f09-5104-4f0d-85ca-8d6cdd28849b',
                    ],
                ],
                [
                    'foo' => 'bar1',
                ],
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => '5cb3f31d-ffb4-4de5-86bd-852825d94ff2',
                    ],
                ],
                [
                    'foo' => 'bar2',
                ],
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => '014aef8c-0b63-4775-9ac6-68d880a11fc7',
                    ],
                ],
                [
                    'foo' => 'bar3',
                ],
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => '21dc5755-93c1-4443-9ee9-3ca0373a1107',
                    ],
                ],
                [
                    'foo' => 'bar4',
                ],
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => '8d429d11-ffdb-4c59-a530-792c5bf028df',
                    ],
                ],
                [
                    'foo' => 'bar5',
                ],
            ],
        ];

        $this->client->expects($this->once())
            ->method('bulk')
            ->with($expectedParameters);

        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument1);
        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument2);
        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument3);
        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument4);
        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument5);
    }

    /**
     * @test
     */
    public function it_can_be_flushed_on_command(): void
    {
        $jsonDocument1 = new JsonDocument('cff29f09-5104-4f0d-85ca-8d6cdd28849b', '{"foo":"bar1"}');
        $jsonDocument2 = new JsonDocument('5cb3f31d-ffb4-4de5-86bd-852825d94ff2', '{"foo":"bar2"}');
        $jsonDocument3 = new JsonDocument('014aef8c-0b63-4775-9ac6-68d880a11fc7', '{"foo":"bar3"}');

        $this->logger->expects($this->exactly(5))
            ->method('info')
            ->withConsecutive(
                ['Queuing document cff29f09-5104-4f0d-85ca-8d6cdd28849b for indexation.'],
                ['Queuing document 5cb3f31d-ffb4-4de5-86bd-852825d94ff2 for indexation.'],
                ['Queuing document 014aef8c-0b63-4775-9ac6-68d880a11fc7 for indexation.'],
                ['Sending 3 documents to ElasticSearch for indexation...'],
                ['Bulk indexation completed.']
            );

        $expectedParameters = [
            'body' => [
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => 'cff29f09-5104-4f0d-85ca-8d6cdd28849b',
                    ],
                ],
                [
                    'foo' => 'bar1',
                ],
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => '5cb3f31d-ffb4-4de5-86bd-852825d94ff2',
                    ],
                ],
                [
                    'foo' => 'bar2',
                ],
                [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->documentType,
                        '_id' => '014aef8c-0b63-4775-9ac6-68d880a11fc7',
                    ],
                ],
                [
                    'foo' => 'bar3',
                ],
            ],
        ];

        $this->client->expects($this->once())
            ->method('bulk')
            ->with($expectedParameters);

        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument1);
        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument2);
        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument3);

        $this->strategy->finish();
    }
}

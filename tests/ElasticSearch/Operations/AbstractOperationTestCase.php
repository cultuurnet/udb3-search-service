<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchIndices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractOperationTestCase extends TestCase
{
    /**
     * @var ElasticSearchClient&MockObject
     */
    protected $client;

    /**
     * @var ElasticSearchIndices&MockObject
     */
    protected $indices;

    /**
     * @var LoggerInterface&MockObject
     */
    protected $logger;

    // @phpstan-ignore-next-line
    protected $operation;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ElasticSearchClient::class);
        $this->indices = $this->createMock(ElasticSearchIndices::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client->expects($this->any())
            ->method('indices')
            ->willReturn($this->indices);

        $this->operation = $this->createOperation($this->client, $this->logger);
    }

    // @phpstan-ignore-next-line
    abstract protected function createOperation(ElasticSearchClient $client, LoggerInterface $logger);
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\Endpoints\Indices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractOperationTestCase extends TestCase
{
    protected ClientInterface&MockObject $client;

    protected Indices&MockObject $indices;

    protected LoggerInterface&MockObject $logger;

    // @phpstan-ignore-next-line
    protected $operation;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->indices = $this->createMock(Indices::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client->expects($this->any())
            ->method('indices')
            ->willReturn($this->indices);

        $this->operation = $this->createOperation($this->client, $this->logger);
    }

    // @phpstan-ignore-next-line
    abstract protected function createOperation(Client $client, LoggerInterface $logger);
}

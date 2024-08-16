<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractOperationTestCase extends TestCase
{
    /**
     * @var Client|MockObject
     */
    protected $client;

    /**
     * @var IndicesNamespace|MockObject
     */
    protected $indices;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    // @phpstan-ignore-next-line
    protected $operation;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->indices = $this->createMock(IndicesNamespace::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client->expects($this->any())
            ->method('indices')
            ->willReturn($this->indices);

        $this->operation = $this->createOperation($this->client, $this->logger);
    }

    // @phpstan-ignore-next-line
    abstract protected function createOperation(Client $client, LoggerInterface $logger);
}

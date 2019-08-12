<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Psr\Log\LoggerInterface;

abstract class AbstractOperationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var IndicesNamespace|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indices;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var mixed
     */
    protected $operation;

    public function setUp()
    {
        $this->client = $this->createMock(Client::class);
        $this->indices = $this->createMock(IndicesNamespace::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client->expects($this->any())
            ->method('indices')
            ->willReturn($this->indices);

        $this->operation = $this->createOperation($this->client, $this->logger);
    }

    abstract protected function createOperation(Client $client, LoggerInterface $logger);
}

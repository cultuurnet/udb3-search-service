<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

class SingleFileIndexationStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var StringLiteral
     */
    private $indexName;

    /**
     * @var StringLiteral
     */
    private $documentType;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var SingleFileIndexationStrategy
     */
    private $strategy;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = new StringLiteral('udb3-core');
        $this->documentType = new StringLiteral('event');

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->strategy = new SingleFileIndexationStrategy(
            $this->client,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_sends_the_document_directly_to_elasticsearch_for_indexation()
    {
        $jsonDocument = new JsonDocument('cff29f09-5104-4f0d-85ca-8d6cdd28849b', '{"foo":"bar"}');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Sending document cff29f09-5104-4f0d-85ca-8d6cdd28849b to ElasticSearch...');

        $this->client->expects($this->once())
            ->method('index')
            ->with(
                [
                    'index' => $this->indexName->toNative(),
                    'type' => $this->documentType->toNative(),
                    'id' => 'cff29f09-5104-4f0d-85ca-8d6cdd28849b',
                    'body' => ['foo' => 'bar'],
                ]
            );

        $this->strategy->indexDocument($this->indexName, $this->documentType, $jsonDocument);
    }
}

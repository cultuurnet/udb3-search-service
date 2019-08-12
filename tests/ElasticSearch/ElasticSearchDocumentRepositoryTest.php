<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use Elasticsearch\Client;
use Psr\Log\NullLogger;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchDocumentRepositoryTest extends \PHPUnit_Framework_TestCase
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
     * @var ElasticSearchDocumentRepository
     */
    private $repository;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexName = new StringLiteral('udb3-core');
        $this->documentType = new StringLiteral('organizer');

        $this->repository = new ElasticSearchDocumentRepository(
            $this->client,
            $this->indexName,
            $this->documentType,
            new SingleFileIndexationStrategy(
                $this->client,
                new NullLogger()
            )
        );
    }

    /**
     * @test
     */
    public function it_indexes_json_documents()
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $body = new \stdClass();
        $body->name = 'STUK';

        $jsonDocument = (new JsonDocument($id))
            ->withBody($body);

        $parameters = [
            'index' => $this->indexName->toNative(),
            'type' => $this->documentType->toNative(),
            'id' => $id,
            'body' => [
                'name' => 'STUK',
            ],
        ];

        $this->client->expects($this->once())
            ->method('index')
            ->with($parameters);

        $this->repository->save($jsonDocument);
    }

    /**
     * @test
     */
    public function it_deletes_documents_on_remove()
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $parameters = [
            'index' => $this->indexName->toNative(),
            'type' => $this->documentType->toNative(),
            'id' => $id,
        ];

        $this->client->expects($this->once())
            ->method('delete')
            ->with($parameters);

        $this->repository->remove($id);
    }

    /**
     * @test
     */
    public function it_returns_stored_documents()
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $parameters = [
            'index' => $this->indexName->toNative(),
            'type' => $this->documentType->toNative(),
            'id' => $id,
        ];

        $response = [
            'found' => true,
            '_index' => $this->indexName->toNative(),
            '_type' => $this->documentType->toNative(),
            '_id' => $id,
            '_version' => 2,
            '_source' => [
                'name' => 'STUK',
            ],
        ];

        $jsonDocument = (new JsonDocument($id))
            ->withBody((object) ['name' => 'STUK']);

        $this->client->expects($this->once())
            ->method('get')
            ->with($parameters)
            ->willReturn($response);

        $this->assertEquals($jsonDocument, $this->repository->get($id));
    }

    /**
     * @test
     */
    public function it_throws_a_document_gone_exception_when_loading_a_deleted_document()
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $parameters = [
            'index' => $this->indexName->toNative(),
            'type' => $this->documentType->toNative(),
            'id' => $id,
        ];

        $response = [
            'found' => false,
            '_index' => $this->indexName->toNative(),
            '_type' => $this->documentType->toNative(),
            '_id' => $id,
            '_version' => 2,
        ];

        $this->client->expects($this->once())
            ->method('get')
            ->with($parameters)
            ->willReturn($response);

        $this->expectException(DocumentGoneException::class);

        $this->repository->get($id);
    }

    /**
     * @test
     */
    public function it_returns_null_for_unknown_documents()
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $parameters = [
            'index' => $this->indexName->toNative(),
            'type' => $this->documentType->toNative(),
            'id' => $id,
        ];

        $response = [
            'found' => false,
            '_index' => $this->indexName->toNative(),
            '_type' => $this->documentType->toNative(),
            '_id' => $id,
        ];

        $this->client->expects($this->once())
            ->method('get')
            ->with($parameters)
            ->willReturn($response);

        $this->assertNull($this->repository->get($id));
    }
}

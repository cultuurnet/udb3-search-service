<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use stdClass;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\MocksElasticsearchResponse;
use CultuurNet\UDB3\Search\ReadModel\DocumentGone;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ElasticSearchDocumentRepositoryTest extends TestCase
{
    use MocksElasticsearchResponse;
    private ElasticSearchClientInterface&MockObject $client;

    private string $indexName;

    private string $documentType;

    private ElasticSearchDocumentRepository $repository;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ElasticSearchClientInterface::class);

        $this->indexName = 'udb3-core';
        $this->documentType = 'organizer';

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
    public function it_indexes_json_documents(): void
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $body = new stdClass();
        $body->name = 'STUK';

        $jsonDocument = (new JsonDocument($id))
            ->withBody($body);

        $this->client->expects($this->once())
            ->method('index')
            ->with([
                'index' => $this->indexName,
                'type' => $this->documentType,
                'id' => $id,
                'body' => [
                    'name' => 'STUK',
                ],
            ]);

        $this->repository->save($jsonDocument);
    }

    /**
     * @test
     */
    public function it_deletes_documents_on_remove(): void
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $this->client->expects($this->once())
            ->method('delete')
            ->with([
                'index' => $this->indexName,
                'type' => $this->documentType,
                'id' => $id,
            ]);

        $this->repository->remove($id);
    }

    /**
     * @test
     */
    public function it_returns_stored_documents(): void
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $response = [
            'found' => true,
            '_index' => $this->indexName,
            '_type' => $this->documentType,
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
            ->with([
                'index' => $this->indexName,
                'type' => $this->documentType,
                'id' => $id,
            ])
            ->willReturn($this->createElasticsearchResponseAsObject($response));

        $this->assertEquals($jsonDocument, $this->repository->get($id));
    }

    /**
     * @test
     */
    public function it_throws_a_document_gone_exception_when_loading_a_deleted_document(): void
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $response = [
            'found' => false,
            '_index' => $this->indexName,
            '_type' => $this->documentType,
            '_id' => $id,
            '_version' => 2,
        ];

        $this->client->expects($this->once())
            ->method('get')
            ->with([
                'index' => $this->indexName,
                'type' => $this->documentType,
                'id' => $id,
            ])
            ->willReturn($this->createElasticsearchResponse($response));

        $this->expectException(DocumentGone::class);

        $this->repository->get($id);
    }

    /**
     * @test
     */
    public function it_returns_null_for_unknown_documents(): void
    {
        $id = '4445a72f-3477-4e8b-b0c2-94cc5fe1bfc4';

        $response = [
            'found' => false,
            '_index' => $this->indexName,
            '_type' => $this->documentType,
            '_id' => $id,
        ];

        $this->client->expects($this->once())
            ->method('get')
            ->with([
                'index' => $this->indexName,
                'type' => $this->documentType,
                'id' => $id,
            ])
            ->willReturn($this->createElasticsearchResponse($response));

        $this->assertNull($this->repository->get($id));
    }
}

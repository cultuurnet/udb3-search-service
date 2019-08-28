<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TransformingJsonDocumentIndexServiceTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $httpClient;

    /**
     * @var ElasticSearchDocumentRepository|MockObject
     */
    private $searchRepository;

    /**
     * @var JsonDocumentTransformerInterface|MockObject
     */
    private $transformer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var TransformingJsonDocumentIndexService
     */
    private $indexService;

    public function setUp()
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->searchRepository = $this->createMock(ElasticSearchDocumentRepository::class);
        $this->transformer = $this->createMock(JsonDocumentTransformerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->indexService = new TransformingJsonDocumentIndexService(
            $this->httpClient,
            $this->transformer,
            $this->searchRepository
        );

        $this->indexService->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_fetches_the_jsonld_from_the_given_url_and_indexes_it_after_transformation()
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $jsonLd = '{"foo":"bar"}';
        $jsonDocument = new JsonDocument($documentId, $jsonLd);

        $transformedJsonLd = '{"foo":"baz"}';
        $transformedJsonDocument = new JsonDocument($documentId, $transformedJsonLd);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $documentUrl)
            ->willReturn(new Response(200, [], $jsonLd));

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($jsonDocument)
            ->willReturn($transformedJsonDocument);

        $this->searchRepository->expects($this->once())
            ->method('save')
            ->with($transformedJsonDocument);

        $this->indexService->index($documentId, $documentUrl);
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_the_jsonld_can_not_be_found()
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $response = new Response(404);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $documentUrl)
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not retrieve JSON-LD from url for indexation.',
                [
                    'id' => $documentId,
                    'url' => $documentUrl,
                    'response' => $response,
                ]
            );

        $this->indexService->index($documentId, $documentUrl);
    }

    /**
     * @test
     */
    public function it_removes_the_given_document_by_id()
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';

        $this->searchRepository->expects($this->once())
            ->method('remove')
            ->with($documentId);

        $this->indexService->remove($documentId);
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_document_can_not_be_removed()
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';

        $exception = new \Exception('Document is already gone', 404);

        $this->searchRepository->expects($this->once())
            ->method('remove')
            ->with($documentId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not remove document from repository.',
                [
                    'id' => $documentId,
                    'exception_code' => $exception->getCode(),
                    'exception_message' => $exception->getMessage(),
                ]
            );

        $this->indexService->remove($documentId);
    }
}

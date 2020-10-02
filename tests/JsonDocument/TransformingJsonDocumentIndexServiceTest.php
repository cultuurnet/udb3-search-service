<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\DocumentRepository;
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
     * @var DocumentRepository|MockObject
     */
    private $searchRepository;

    /**
     * @var JsonTransformer|MockObject
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

    protected function setUp()
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->searchRepository = $this->createMock(DocumentRepository::class);
        $this->transformer = $this->createMock(JsonTransformer::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->indexService = new TransformingJsonDocumentIndexService(
            $this->httpClient,
            new JsonDocumentTransformer($this->transformer),
            $this->searchRepository
        );

        $this->indexService->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_fetches_the_jsonld_from_the_given_url_and_indexes_it_after_transformation(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $jsonLd = ['foo' => 'bar'];
        $transformedJsonLd = ['foo' => 'baz'];
        $transformedJsonDocument = (new JsonDocument($documentId))
            ->withBody($transformedJsonLd);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $documentUrl)
            ->willReturn(new Response(200, [], json_encode($jsonLd)));

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($jsonLd, [])
            ->willReturn($transformedJsonLd);

        $this->searchRepository->expects($this->once())
            ->method('save')
            ->with($transformedJsonDocument);

        $this->indexService->index($documentId, $documentUrl);
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_the_jsonld_can_not_be_found(): void
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
    public function it_removes_the_given_document_by_id(): void
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

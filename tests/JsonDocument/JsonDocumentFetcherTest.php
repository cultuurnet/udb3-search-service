<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JsonDocumentFetcherTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $httpClient;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    private GuzzleJsonDocumentFetcher $jsonDocumentFetcher;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jsonDocumentFetcher = (new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger
        ))->withIncludeMetadata();
    }

    /**
     * @test
     */
    public function it_can_fetch_json_document_with_metadata(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $jsonLd = ['foo' => 'bar'];
        $expectedJsonDocument = (new JsonDocument($documentId))
            ->withBody($jsonLd);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                [
                    'query' => [
                        'includeMetadata' => true,
                        'embedUitpasPrices' => true,
                    ],
                ]
            )
            ->willReturn(
                new Response(200, [], Json::encode($jsonLd))
            );

        $actualJsonDocument = $this->jsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );

        $this->assertEquals($expectedJsonDocument, $actualJsonDocument);
    }

    /**
     * @test
     */
    public function it_can_fetch_json_document_without_metadata(): void
    {
        $jsonDocumentFetcher = new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger
        );

        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $jsonLd = ['foo' => 'bar'];
        $expectedJsonDocument = (new JsonDocument($documentId))
            ->withBody($jsonLd);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                []
            )
            ->willReturn(
                new Response(200, [], Json::encode($jsonLd))
            );

        $actualJsonDocument = $jsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );

        $this->assertEquals($expectedJsonDocument, $actualJsonDocument);
    }

    /**
     * @test
     */
    public function it_returns_null_on_http_error(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                [
                    'query' => [
                        'includeMetadata' => true,
                        'embedUitpasPrices' => true,
                    ],
                ]
            )
            ->willReturn(
                new Response(400)
            );

        $actualJsonDocument = $this->jsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );

        $this->assertNull($actualJsonDocument);
    }

    /**
     * @test
     */
    public function it_logs_on_http_error(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                [
                    'query' => [
                        'includeMetadata' => true,
                        'embedUitpasPrices' => true,
                    ],
                ]
            )
            ->willReturn(
                new Response(400)
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not retrieve JSON-LD from url for indexation.');

        $this->jsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );
    }
}

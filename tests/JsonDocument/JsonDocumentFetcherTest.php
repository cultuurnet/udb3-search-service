<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Http\Authentication\Token\Token;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use DateTimeImmutable;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JsonDocumentFetcherTest extends TestCase
{
    private const DUMMY_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

    /**
     * @var ClientInterface|MockObject
     */
    private $httpClient;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var TokenGenerator|MockObject
     */
    private $tokenGenerator;

    private GuzzleJsonDocumentFetcher $jsonDocumentFetcher;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);

        $this->tokenGenerator = $this->createMock(TokenGenerator::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->jsonDocumentFetcher = new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger,
            $this->tokenGenerator
        );
    }

    /**
     * @test
     */
    public function it_can_fetch_json_document_with_embed_contributors(): void
    {
        $jsonDocumentFetcher = $this->jsonDocumentFetcher->withEmbedContributors();

        $this->givenAValidLoginTokenIsReturned();

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
                        'embedContributors' => true,
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . self::DUMMY_TOKEN,
                    ],
                ]
            )
            ->willReturn(
                new Response(200, [], Json::encode($jsonLd))
            );

        $actualJsonDocument = $jsonDocumentFetcher
            ->withIncludeMetadata()
            ->fetch(
                $documentId,
                $documentUrl
            );

        $this->assertEquals($expectedJsonDocument, $actualJsonDocument);
    }

    /**
     * @test
     */
    public function it_can_fetch_json_document_with_metadata(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->givenAValidLoginTokenIsReturned();

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
                    'headers' => [
                        'Authorization' => 'Bearer ' . self::DUMMY_TOKEN,
                    ],
                ]
            )
            ->willReturn(
                new Response(200, [], Json::encode($jsonLd))
            );

        $actualJsonDocument = $this->jsonDocumentFetcher
            ->withIncludeMetadata()
            ->fetch(
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
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->givenAValidLoginTokenIsReturned();

        $jsonLd = ['foo' => 'bar'];
        $expectedJsonDocument = (new JsonDocument($documentId))
            ->withBody($jsonLd);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . self::DUMMY_TOKEN,
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
    public function it_returns_null_on_http_error(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->givenAValidLoginTokenIsReturned();

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
                    'headers' => [
                        'Authorization' => 'Bearer ' . self::DUMMY_TOKEN,
                    ],
                ]
            )
            ->willReturn(
                new Response(400)
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not retrieve JSON-LD from url for indexation.');

        $actualJsonDocument = $this->jsonDocumentFetcher
            ->withIncludeMetadata()
            ->fetch(
                $documentId,
                $documentUrl
            );

        $this->assertNull($actualJsonDocument);
    }

    /**
     * @test
     */
    public function it_can_refresh_tokens_on_a_401(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->givenARefreshTokenIsRequired();

        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                [
                    'headers' =>[
                        'Authorization' => 'Bearer ' . self::DUMMY_TOKEN,
                    ],
                    'query' => [
                        'includeMetadata' => true,
                        'embedUitpasPrices' => true,
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Response(401),
                new Response(200)
            );

        $this->jsonDocumentFetcher
            ->withIncludeMetadata()
            ->fetch(
                $documentId,
                $documentUrl
            );
    }

    /**
     * @test
     */
    public function it_fails_when_refreshed_token_is_invalid(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->givenAnInvalidTokenIsReturned();

        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->with(
                'GET',
                $documentUrl,
                [
                    'headers' =>[
                        'Authorization' => 'Bearer ' . self::DUMMY_TOKEN,
                    ],
                    'query' => [
                        'includeMetadata' => true,
                        'embedUitpasPrices' => true,
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Response(401),
                new Response(401)
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not retrieve JSON-LD from url for indexation.');

        $this->jsonDocumentFetcher
            ->withIncludeMetadata()
            ->fetch(
                $documentId,
                $documentUrl
            );
    }

    private function givenAValidLoginTokenIsReturned(): void
    {
        $this->tokenGenerator->expects($this->once())
            ->method('loginToken')
            ->willReturn(
                new Token(
                    self::DUMMY_TOKEN,
                    new DateTimeImmutable(),
                    3600
                )
            );
    }

    private function givenARefreshTokenIsRequired(): void
    {
        $this->tokenGenerator->expects($this->exactly(2))
            ->method('loginToken')
            ->willReturnOnConsecutiveCalls(
                new Token(
                    self::DUMMY_TOKEN,
                    new DateTimeImmutable(),
                    1 // Token needs to be valid for more than 5 minutes
                ),
                new Token(
                    self::DUMMY_TOKEN,
                    new DateTimeImmutable(),
                    3600
                )
            );
    }

    private function givenAnInvalidTokenIsReturned(): void
    {
        $this->tokenGenerator->expects($this->exactly(2))
            ->method('loginToken')
            ->willReturnOnConsecutiveCalls(
                new Token(
                    self::DUMMY_TOKEN,
                    new DateTimeImmutable(),
                    1 // Token needs to be valid for more than 5 minutes
                ),
                new Token(
                    self::DUMMY_TOKEN,
                    new DateTimeImmutable(),
                    1 // Simulate invalid token by making it expired
                )
            );
    }
}

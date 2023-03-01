<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Http\Authentication\Auth0Client;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JsonDocumentFetcherTest extends TestCase
{
    private const DUMMY_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
    private const DOMAIN = 'domain.com';
    private const CLIENT_ID = 'client_id';
    private const CLIENT_SECRET = 'client_secret';

    /**
     * @var ClientInterface|MockObject
     */
    private $httpClient;

    /**
     * @var Client|MockObject
     */
    private $auth0httpClient;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    private GuzzleJsonDocumentFetcher $jsonDocumentFetcher;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->auth0httpClient = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jsonDocumentFetcher = (new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger,
            new Auth0Client(
                $this->auth0httpClient,
                self::DOMAIN,
                self::CLIENT_ID,
                self::CLIENT_SECRET
            )
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
            $this->logger,
            new Auth0Client(
                $this->auth0httpClient,
                self::DOMAIN,
                self::CLIENT_ID,
                self::CLIENT_SECRET
            )
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

    /**
     * @test
     */
    public function it_can_authorize_requests(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->auth0httpClient->expects($this->once())
            ->method('post')
            ->with(
                'https://' . self::DOMAIN . '/oauth/token',
                [
                    'headers' => ['content-type' => 'application/json'],
                    'json' => [
                        'client_id' => self::CLIENT_ID,
                        'client_secret' => self::CLIENT_SECRET,
                        'audience' => 'https://' . self::DOMAIN . '/api/v2/',
                        'grant_type' => 'client_credentials',
                    ],
                ]
            )
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode([
                        'access_token' => self::DUMMY_TOKEN,
                        'expires_in' => 86400000,
                    ])
                )
            );

        $authorizedJsonDocumentFetcher = (new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger,
            new Auth0Client(
                $this->auth0httpClient,
                self::DOMAIN,
                self::CLIENT_ID,
                self::CLIENT_SECRET
            )
        ))->withIncludeMetadata();

        $this->httpClient->expects($this->once())
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
            ->willReturn(
                new Response(200)
            );

        $authorizedJsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );
    }

    /**
     * @test
     */
    public function it_can_refresh_tokens_on_a_401(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->auth0httpClient->expects($this->exactly(2))
            ->method('post')
            ->with(
                'https://' . self::DOMAIN . '/oauth/token',
                [
                    'headers' => ['content-type' => 'application/json'],
                    'json' => [
                        'client_id' => self::CLIENT_ID,
                        'client_secret' => self::CLIENT_SECRET,
                        'audience' => 'https://' . self::DOMAIN . '/api/v2/',
                        'grant_type' => 'client_credentials',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Response(
                    200,
                    [],
                    Json::encode([
                        'access_token' => self::DUMMY_TOKEN,
                        'expires_in' => 1,
                    ])
                ),
                new Response(
                    200,
                    [],
                    Json::encode([
                        'access_token' => self::DUMMY_TOKEN,
                        'expires_in' => 10000,
                    ])
                ),
            );

        $authorizedJsonDocumentFetcher = (new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger,
            new Auth0Client(
                $this->auth0httpClient,
                self::DOMAIN,
                self::CLIENT_ID,
                self::CLIENT_SECRET
            )
        ))->withIncludeMetadata();

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

        $authorizedJsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );
    }

    /**
     * @test
     */
    public function it_will_not_refresh_tokens_infinitely(): void
    {
        $documentId = '23017cb7-e515-47b4-87c4-780735acc942';
        $documentUrl = 'event/' . $documentId;

        $this->auth0httpClient->expects($this->exactly(2))
            ->method('post')
            ->with(
                'https://' . self::DOMAIN . '/oauth/token',
                [
                    'headers' => ['content-type' => 'application/json'],
                    'json' => [
                        'client_id' => self::CLIENT_ID,
                        'client_secret' => self::CLIENT_SECRET,
                        'audience' => 'https://' . self::DOMAIN . '/api/v2/',
                        'grant_type' => 'client_credentials',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Response(
                    200,
                    [],
                    Json::encode([
                        'access_token' => self::DUMMY_TOKEN,
                        'expires_in' => 1,
                    ])
                ),
                new Response(
                    200,
                    [],
                    Json::encode([
                        'access_token' => self::DUMMY_TOKEN,
                        'expires_in' => 1,
                    ])
                )
            );

        $authorizedJsonDocumentFetcher = (new GuzzleJsonDocumentFetcher(
            $this->httpClient,
            $this->logger,
            new Auth0Client(
                $this->auth0httpClient,
                self::DOMAIN,
                self::CLIENT_ID,
                self::CLIENT_SECRET
            )
        ))->withIncludeMetadata();

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

        $authorizedJsonDocumentFetcher->fetch(
            $documentId,
            $documentUrl
        );
    }
}

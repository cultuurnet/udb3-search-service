<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use Crell\ApiProblem\ApiProblem;
use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\NotAllowedToUseSapi;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\RemovedApiKey;
use CultuurNet\UDB3\Search\Http\DefaultQuery\InMemoryDefaultQueryRepository;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use ICultureFeed;
use League\Container\Container;
use League\Container\Definition\DefinitionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AuthenticateRequestTest extends TestCase
{
    private const BEARER = 'Bearer ';

    /**
     * @var Container|MockObject
     */
    private $container;

    /**
     * @var ICultureFeed|MockObject
     */
    private $cultureFeed;

    private Auth0TokenProvider $auth0TokenProvider;

    private AuthenticateRequest $authenticateRequest;

    private string $pemFile;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);
        $this->cultureFeed = $this->createMock(ICultureFeed::class);

        $this->pemFile = file_get_contents(__DIR__ . '/samples/public.pem');

        $auth0Client = new Auth0Client(
            $this->createMock(Client::class),
            'domain',
            'clientId',
            'clientSecret'
        );

        $auth0TokenRepository = $this->createMock(Auth0TokenRepository::class);
        $auth0TokenRepository
            ->method('get')
            ->willReturn(
                new Auth0Token(
                    'my_auth0_token',
                    new DateTimeImmutable(),
                    86400
                )
            );

        $this->auth0TokenProvider = new Auth0TokenProvider(
            $auth0TokenRepository,
            $auth0Client
        );

        $this->authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->cultureFeed,
            $this->auth0TokenProvider,
            $auth0Client,
            new InMemoryDefaultQueryRepository(['my_active_api_key' => 'my_default_search_query']),
            $this->pemFile
        );
    }

    /**
     * @test
     */
    public function it_does_not_handle_option_requests(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('OPTIONS', 'https://search.uitdatabank.be')
            ->withHeader('Access-Control-Request-Method', 'GET');

        $response = (new ResponseFactory())->createResponse(200);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $actualResponse = $this->authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    /**
     * @test
     */
    public function it_handles_missing_credentials(): void
    {
        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())->createServerRequest('GET', 'https://search.uitdatabank.be'),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(new MissingCredentials(), $response);
    }

    /**
     * @test
     */
    public function it_handles_invalid_api_keys(): void
    {
        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_invalid_api_key', true)
            ->willThrowException(new Exception('Invalid API key'));

        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('x-api-key', 'my_invalid_api_key'),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(new InvalidApiKey('my_invalid_api_key'), $response);
    }

    /**
     * @test
     */
    public function it_handles_blocked_api_keys(): void
    {
        $cultureFeedConsumer = new CultureFeed_Consumer();
        $cultureFeedConsumer->status = 'BLOCKED';

        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_blocked_api_key', true)
            ->willReturn($cultureFeedConsumer);

        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('x-api-key', 'my_blocked_api_key'),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(new BlockedApiKey('my_blocked_api_key'), $response);
    }

    /**
     * @test
     */
    public function it_handles_removed_api_keys(): void
    {
        $cultureFeedConsumer = new CultureFeed_Consumer();
        $cultureFeedConsumer->status = 'REMOVED';

        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_removed_api_key', true)
            ->willReturn($cultureFeedConsumer);

        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('x-api-key', 'my_removed_api_key'),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(new RemovedApiKey('my_removed_api_key'), $response);
    }

    /**
     * @dataProvider validApiKeyRequestsProvider
     * @test
     */
    public function it_handles_valid_requests_with_api_key(ServerRequestInterface $request): void
    {
        $cultureFeedConsumer = new CultureFeed_Consumer();
        $cultureFeedConsumer->status = 'ACTIVE';
        $cultureFeedConsumer->searchPrefixSapi3 = 'my_default_search_query';

        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_active_api_key', true)
            ->willReturn($cultureFeedConsumer);

        $response = (new ResponseFactory())->createResponse(200);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $definitionInterface = $this->createMock(DefinitionInterface::class);
        $definitionInterface->expects($this->once())
            ->method('setConcrete')
            ->with(new Consumer('my_active_api_key', 'my_default_search_query'));

        $this->container->expects($this->once())
            ->method('extend')
            ->with(Consumer::class)
            ->willReturn($definitionInterface);

        $actualResponse = $this->authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    /**
     * @dataProvider validApiKeyRequestsProvider
     * @test
     */
    public function it_handles_valid_requests_with_api_key_and_default_query_config(ServerRequestInterface $request): void
    {
        $cultureFeedConsumer = new CultureFeed_Consumer();
        $cultureFeedConsumer->status = 'ACTIVE';

        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_active_api_key', true)
            ->willReturn($cultureFeedConsumer);

        $response = (new ResponseFactory())->createResponse(200);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $definitionInterface = $this->createMock(DefinitionInterface::class);
        $definitionInterface->expects($this->once())
            ->method('setConcrete')
            ->with(new Consumer('my_active_api_key', 'my_default_search_query'));

        $this->container->expects($this->once())
            ->method('extend')
            ->with(Consumer::class)
            ->willReturn($definitionInterface);

        $actualResponse = $this->authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    public function validApiKeyRequestsProvider(): array
    {
        return [
            'api key header' => [
                (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withHeader('x-api-key', 'my_active_api_key'),
            ],
            'api key param' => [
                (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withQueryParams(['apiKey' => 'my_active_api_key']),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_requests_with_client_id_with_missing_sapi_permission_in_metadata(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], Json::encode([
                'client_metadata' => ['publiq-apis' => 'ups entry'],
            ])),
        ]);

        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->cultureFeed,
            $this->auth0TokenProvider,
            new Auth0Client(
                new Client(['handler' => $mockHandler]),
                'domain',
                'clientId',
                'clientSecret'
            ),
            new InMemoryDefaultQueryRepository([]),
            $this->pemFile
        );

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->never())
            ->method('handle');

        $this->container->expects($this->never())
            ->method('extend');

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('x-client-id', 'my_active_client_id');
        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertProblemReport(new NotAllowedToUseSapi('my_active_client_id'), $actualResponse);
    }

    /**
     * @test
     */
    public function it_handles_requests_with_client_id_without_metadata(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], Json::encode([])),
        ]);

        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->cultureFeed,
            $this->auth0TokenProvider,
            new Auth0Client(
                new Client(['handler' => $mockHandler]),
                'domain',
                'clientId',
                'clientSecret'
            ),
            new InMemoryDefaultQueryRepository([]),
            $this->pemFile
        );

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->never())
            ->method('handle');

        $this->container->expects($this->never())
            ->method('extend');

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('x-client-id', 'my_active_client_id');
        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertProblemReport(new NotAllowedToUseSapi('my_active_client_id'), $actualResponse);
    }

    /**
     * @test
     */
    public function it_allows_all_access_when_auth0_is_down(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('x-client-id', 'my_active_client_id');

        $mockHandler = new MockHandler([new ConnectException('No connection with Auth0', $request)]);

        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->cultureFeed,
            $this->auth0TokenProvider,
            new Auth0Client(
                new Client(['handler' => $mockHandler]),
                'domain',
                'clientId',
                'clientSecret'
            ),
            new InMemoryDefaultQueryRepository([]),
            '../data/'
        );

        $response = (new ResponseFactory())->createResponse(200);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $definitionInterface = $this->createMock(DefinitionInterface::class);
        $definitionInterface->expects($this->once())
            ->method('setConcrete')
            ->with(new Consumer('my_active_client_id', null));

        $this->container->expects($this->once())
            ->method('extend')
            ->with(Consumer::class)
            ->willReturn($definitionInterface);

        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    /**
     * @dataProvider validClientIdRequestsProvider
     * @test
     */
    public function it_handles_valid_requests_with_client_id(ServerRequestInterface $request): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], Json::encode([
                'client_metadata' => ['publiq-apis' => 'ups entry sapi'],
            ])),
        ]);

        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->cultureFeed,
            $this->auth0TokenProvider,
            new Auth0Client(
                new Client(['handler' => $mockHandler]),
                'domain',
                'clientId',
                'clientSecret'
            ),
            new InMemoryDefaultQueryRepository([]),
            $this->pemFile
        );

        $response = (new ResponseFactory())->createResponse(200);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $definitionInterface = $this->createMock(DefinitionInterface::class);
        $definitionInterface->expects($this->once())
            ->method('setConcrete')
            ->with(new Consumer('my_active_client_id', null));

        $this->container->expects($this->once())
            ->method('extend')
            ->with(Consumer::class)
            ->willReturn($definitionInterface);

        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    public function validClientIdRequestsProvider(): array
    {
        return [
            'client id header' => [
                $request = (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withHeader('x-client-id', 'my_active_client_id'),
            ],
            'client id param' => [
                $request = (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withQueryParams(['clientId' => 'my_active_client_id']),
            ],
        ];
    }

    private function assertProblemReport(ApiProblem $apiProblem, ResponseInterface $response): void
    {
        $response->getBody()->rewind();
        $this->assertEquals($apiProblem->getStatus(), $response->getStatusCode());
        $this->assertEquals('application/ld+json', $response->getHeader('Content-Type')[0]);
        $this->assertEquals(Json::encodeWithOptions($apiProblem->asArray(), JSON_HEX_QUOT), $response->getBody()->getContents());
    }
}

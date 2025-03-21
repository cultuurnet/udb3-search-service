<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Http\Authentication\Access\ConsumerResolver;
use CultuurNet\UDB3\Search\Http\Authentication\Access\ClientIdResolver;
use CultuurNet\UDB3\Search\Http\Authentication\Access\InvalidConsumer;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidToken;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\NotAllowedToUseSapi;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\RemovedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\Token\Token;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenRepository;
use CultuurNet\UDB3\Search\Http\DefaultQuery\InMemoryDefaultQueryRepository;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use League\Container\Container;
use League\Container\Definition\DefinitionInterface;
use Noodlehaus\Config;
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
     * @var Container&MockObject
     */
    private $container;

    /**
     * @var ConsumerResolver&MockObject
     */
    private $consumerResolver;

    /**
     * @var ClientIdResolver&MockObject
     */
    private $clientIdResolver;

    private AuthenticateRequest $authenticateRequest;

    private string $pemFile;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);
        $this->container
            ->method('get')
            ->willReturn(new Config([]));

        $this->consumerResolver = $this->createMock(ConsumerResolver::class);

        $this->pemFile = FileReader::read(__DIR__ . '/samples/public.pem');

        $managementToken = new Token(
            'my_oauth_token',
            new DateTimeImmutable(),
            86400
        );

        /** @var TokenGenerator&MockObject $managementTokenGenerator */
        $managementTokenGenerator = $this->createMock(TokenGenerator::class);
        $managementTokenGenerator
            ->method('managementToken')
            ->willReturn($managementToken);

        /** @var ManagementTokenRepository&MockObject $managementTokenRepository */
        $managementTokenRepository = $this->createMock(ManagementTokenRepository::class);
        $managementTokenRepository
            ->method('get')
            ->willReturn($managementToken);

        $this->clientIdResolver = $this->createMock(ClientIdResolver::class);

        $this->authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->consumerResolver,
            $this->clientIdResolver,
            new InMemoryDefaultQueryRepository([
                'api_keys' =>
                    ['my_active_api_key_with_config_query' => 'my_default_search_query'],
            ]),
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
        $this->consumerResolver->expects($this->once())
            ->method('getStatus')
            ->with('my_invalid_api_key')
            ->willThrowException(new InvalidConsumer());

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
        $this->consumerResolver->expects($this->once())
            ->method('getStatus')
            ->with('my_blocked_api_key')
            ->willReturn('BLOCKED');

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
        $this->consumerResolver->expects($this->once())
            ->method('getStatus')
            ->with('my_removed_api_key')
            ->willReturn('REMOVED');

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
        $this->consumerResolver->expects($this->once())
            ->method('getStatus')
            ->with('my_active_api_key')
            ->willReturn('ACTIVE');

        $this->consumerResolver->expects($this->once())
            ->method('getDefaultQuery')
            ->with('my_active_api_key')
            ->willReturn('my_default_search_query');

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
        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->consumerResolver,
            $this->clientIdResolver,
            new InMemoryDefaultQueryRepository([
                'api_keys' => ['my_active_api_key' => 'my_default_search_query'],
            ]),
            $this->pemFile
        );

        $this->consumerResolver->expects($this->once())
            ->method('getStatus')
            ->with('my_active_api_key')
            ->willReturn('ACTIVE');

        $this->consumerResolver->expects($this->never())
            ->method('getDefaultQuery')
            ->with('my_active_api_key');

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

        $actualResponse = $authenticateRequest->process($request, $requestHandler);

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

    public function validApiKeyWithConfigQueryRequestsProvider(): array
    {
        return [
            'api key header' => [
                (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withHeader('x-api-key', 'my_active_api_key_with_config_query'),
            ],
            'api key param' => [
                (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withQueryParams(['apiKey' => 'my_active_api_key_with_config_query']),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_unallowed_requests_with_a_client_id(): void
    {
        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->consumerResolver,
            $this->clientIdResolver,
            new InMemoryDefaultQueryRepository([]),
            $this->pemFile
        );

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->never())
            ->method('handle');

        $this->container->expects($this->never())
            ->method('extend');

        $this->clientIdResolver->expects($this->once())
            ->method('hasSapiAccess')
            ->with('my_active_client_id')
            ->willReturn(false);

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('x-client-id', 'my_active_client_id');
        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertProblemReport(new NotAllowedToUseSapi('my_active_client_id'), $actualResponse);
    }

    /**
     * @dataProvider validClientIdRequestsProvider
     * @test
     */
    public function it_handles_valid_requests_with_client_id(ServerRequestInterface $request): void
    {
        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->consumerResolver,
            $this->clientIdResolver,
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

        $this->clientIdResolver->expects($this->once())
            ->method('hasSapiAccess')
            ->with('my_active_client_id')
            ->willReturn(true);

        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    /**
     * @dataProvider validClientIdRequestsProvider
     * @test
     */
    public function it_handles_valid_requests_with_client_id_and_default_query(ServerRequestInterface $request): void
    {
        $authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->consumerResolver,
            $this->clientIdResolver,
            new InMemoryDefaultQueryRepository([
                'client_ids' => ['my_active_client_id' => 'my_new_default_search_query'],
            ]),
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
            ->with(new Consumer('my_active_client_id', 'my_new_default_search_query'));

        $this->container->expects($this->once())
            ->method('extend')
            ->with(Consumer::class)
            ->willReturn($definitionInterface);

        $this->clientIdResolver->expects($this->once())
            ->method('hasSapiAccess')
            ->with('my_active_client_id')
            ->willReturn(true);

        $actualResponse = $authenticateRequest->process($request, $requestHandler);

        $this->assertEquals($response, $actualResponse);
    }

    public function validClientIdRequestsProvider(): array
    {
        return [
            'client id header' => [
                (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withHeader('x-client-id', 'my_active_client_id'),
            ],
            'client id param' => [
                (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withQueryParams(['clientId' => 'my_active_client_id']),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_valid_requests_with_a_token(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(['https://publiq.be/publiq-apis' => 'sapi']);

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('authorization', self::BEARER . $token);
        $expectedResponse = (new ResponseFactory())->createResponse(200);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $actualResponse = $this->authenticateRequest->process(
            $request,
            $requestHandler
        );

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function it_handles_unallowed_requests_with_a_token(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(['https://publiq.be/publiq-apis' => 'entry']);
        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('authorization', self::BEARER . $token),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(
            new NotAllowedToUseSapi(),
            $response
        );
    }

    /**
     * @dataProvider v2Claims
     * @test
     */
    public function it_handles_unallowed_requests_with_v2_tokens(array $claims): void
    {
        $token = JsonWebTokenFactory::createWithClaims($claims);
        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('authorization', self::BEARER . $token),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(
            new NotAllowedToUseSapi(),
            $response
        );
    }

    public function v2Claims(): array
    {
        return [
            'only nickname' => [
                [
                    'https://publiq.be/publiq-apis' => 'sapi',
                    'nickname' => 'foobar',
                ],
            ],
            'only email' => [
                [
                    'https://publiq.be/publiq-apis' => 'sapi',
                    'email' => 'foo@bar.com',
                ],
            ],
            'nickname and email' => [
                [
                    'https://publiq.be/publiq-apis' => 'sapi',
                    'nickname' => 'foobar',
                    'email' => 'foo@bar.com',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_requests_without_bearer_in_the_token(): void
    {
        $token = JsonWebTokenFactory::createWithClaims([]);
        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('authorization', $token),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(
            new InvalidToken('Authorization header must start with "' . self::BEARER . '", followed by your token'),
            $response
        );
    }

    /**
     * @test
     */
    public function it_handles_invalid_token(): void
    {
        $invalidToken = JsonWebTokenFactory::createWithInvalidSignature();
        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('authorization', self::BEARER . $invalidToken),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(
            new InvalidToken('Token "' . $invalidToken . '" is expired or not valid for Search API.'),
            $response
        );
    }

    /**
     * @test
     */
    public function it_handles_requests_with_unparsable_token(): void
    {
        $unparsableToken = '123';
        $response = $this->authenticateRequest->process(
            (new ServerRequestFactory())
                ->createServerRequest('GET', 'https://search.uitdatabank.be')
                ->withHeader('authorization', self::BEARER . $unparsableToken),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertProblemReport(
            new InvalidToken('Token "' . $unparsableToken . '" is not a valid JWT.'),
            $response
        );
    }

    private function assertProblemReport(ApiProblem $apiProblem, ResponseInterface $response): void
    {
        $response->getBody()->rewind();
        $this->assertEquals($apiProblem->getStatus(), $response->getStatusCode());
        $this->assertEquals('application/ld+json', $response->getHeader('Content-Type')[0]);
        $this->assertEquals(Json::encodeWithOptions($apiProblem->asArray(), JSON_HEX_QUOT), $response->getBody()->getContents());
    }
}

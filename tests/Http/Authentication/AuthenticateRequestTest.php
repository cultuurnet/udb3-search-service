<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use Auth0\SDK\API\Management as Auth0Management;
use Crell\ApiProblem\ApiProblem;
use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\RemovedApiKey;
use Exception;
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
    /**
     * @var Container|MockObject
     */
    private $container;

    /**
     * @var ICultureFeed|MockObject
     */
    private $cultureFeed;

    /**
     * @var Auth0Management|MockObject
     */
    private $auth0Management;

    /**
     * @var AuthenticateRequest
     */
    private $authenticateRequest;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);
        $this->cultureFeed = $this->createMock(ICultureFeed::class);
        $this->auth0Management = $this->createMock(Auth0Management::class);

        $this->authenticateRequest = new AuthenticateRequest(
            $this->container,
            $this->cultureFeed,
            $this->auth0Management
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
     * @dataProvider validRequestsProvider
     * @test
     */
    public function it_handles_valid_requests(ServerRequestInterface $request): void
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

    public function validRequestsProvider(): array
    {
        return [
            'api key header' => [
                $request = (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withHeader('x-api-key', 'my_active_api_key'),
            ],
            'api key param' => [
                $request = (new ServerRequestFactory())
                    ->createServerRequest('GET', 'https://search.uitdatabank.be')
                    ->withQueryParams(['apiKey' => 'my_active_api_key']),
            ],
        ];
    }

    /**
     * @dataProvider validClientIdRequestsProvider
     * @test
     */
    public function it_handles_valid_requests_with_client_id(ServerRequestInterface $request): void
    {
        $auth0Clients = $this->createMock(Clients::class);
        $auth0Clients->expects($this->once())
            ->method('get')
            ->with(
                'my_active_client_id',
                ['client_id', 'client_metadata']
            )
            ->willReturn([
                'client_metadata' => [
                    'sapi3' => true,
                ],
            ]);

        $this->auth0Management->expects($this->once())
            ->method('clients')
            ->willReturn($auth0Clients);

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

        $actualResponse = $this->authenticateRequest->process($request, $requestHandler);

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
        $this->assertEquals($apiProblem->getStatus(), $response->getStatusCode());
        $this->assertEquals('application/ld+json', $response->getHeader('Content-Type')[0]);
        $this->assertEquals(json_encode($apiProblem->asArray()), $response->getBody());
    }
}

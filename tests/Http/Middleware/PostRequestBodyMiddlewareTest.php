<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

final class PostRequestBodyMiddlewareTest extends TestCase
{
    private PostRequestBodyMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new PostRequestBodyMiddleware();
    }

    /**
     * @test
     */
    public function it_passes_get_requests_through_unchanged(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be/offers?name=test&limit=5');

        $expectedResponse = new Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $passedRequest) use ($request) {
                return $passedRequest->getQueryParams() === $request->getQueryParams();
            }))
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @test
     */
    public function it_parses_text_plain_post_body_as_query_params(): void
    {
        $body = 'name=test&limit=5&regions=gem-leuven';

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://search.uitdatabank.be/offers')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody((new StreamFactory())->createStream($body));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $passedRequest) {
                $params = $passedRequest->getQueryParams();
                return $params['name'] === 'test'
                    && $params['limit'] === '5'
                    && $params['regions'] === 'gem-leuven';
            }))
            ->willReturn(new Response());

        $this->middleware->process($request, $handler);
    }

    /**
     * @test
     */
    public function it_returns_415_when_post_has_no_content_type(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://search.uitdatabank.be/offers');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(StatusCodeInterface::STATUS_UNSUPPORTED_MEDIA_TYPE, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_415_when_post_has_wrong_content_type(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://search.uitdatabank.be/offers')
            ->withHeader('Content-Type', 'application/json');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(StatusCodeInterface::STATUS_UNSUPPORTED_MEDIA_TYPE, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_post_with_empty_body(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://search.uitdatabank.be/offers')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody((new StreamFactory())->createStream(''));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $passedRequest) {
                return $passedRequest->getQueryParams() === [];
            }))
            ->willReturn(new Response());

        $this->middleware->process($request, $handler);
    }

    /**
     * @test
     */
    public function it_replaces_url_query_params_with_body_params_on_post(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', 'https://search.uitdatabank.be/offers?name=fromUrl&start=10')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody((new StreamFactory())->createStream('name=fromBody&limit=5'));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $passedRequest) {
                $params = $passedRequest->getQueryParams();
                return $params === ['name' => 'fromBody', 'limit' => '5'];
            }))
            ->willReturn(new Response());

        $this->middleware->process($request, $handler);
    }
}

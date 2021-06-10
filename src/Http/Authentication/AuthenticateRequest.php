<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\RemovedApiKey;
use Exception;
use ICultureFeed;
use League\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthenticateRequest implements MiddlewareInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var ICultureFeed
     */
    private $cultureFeed;

    public function __construct(Container $container, ICultureFeed $cultureFeed)
    {
        $this->container = $container;
        $this->cultureFeed = $cultureFeed;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method')) {
            return $handler->handle($request);
        }

        $apiKey = $this->getApiKey($request);

        if (empty($apiKey)) {
            return (new MissingCredentials())->toResponse();
        }

        try {
            /** @var CultureFeed_Consumer $cultureFeedConsumer */
            $cultureFeedConsumer = $this->cultureFeed->getServiceConsumerByApiKey($apiKey, true);
        } catch (Exception $exception) {
            return (new InvalidApiKey($apiKey))->toResponse();
        }

        if ($cultureFeedConsumer->status === 'BLOCKED') {
            return (new BlockedApiKey($apiKey))->toResponse();
        }

        if ($cultureFeedConsumer->status === 'REMOVED') {
            return (new RemovedApiKey($apiKey))->toResponse();
        }

        $this->container->add(Consumer::class, new Consumer($apiKey, $cultureFeedConsumer->searchPrefixSapi3));

        return $handler->handle($request);
    }

    private function getApiKey(ServerRequestInterface $request): ?string
    {
        if ($this->getHeaderValue($request, 'x-api-key')) {
            return $this->getHeaderValue($request, 'x-api-key');
        }

        if ($this->getParamValue($request, 'apiKey')) {
            return $this->getParamValue($request, 'apiKey');
        }

        return null;
    }

    private function getHeaderValue(ServerRequestInterface $request, string $headerName): ?string
    {
        $headerValues = $request->getHeader($headerName);

        if (empty($headerValues)) {
            return null;
        }

        return $headerValues[0];
    }

    private function getParamValue(ServerRequestInterface $request, string $paramName): ?string
    {
        $queryParams = $request->getQueryParams();

        if (empty($queryParams)) {
            return null;
        }

        return $queryParams[$paramName] ?? null;
    }
}
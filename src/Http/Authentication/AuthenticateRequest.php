<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use Auth0\SDK\API\Management as Auth0Management;
use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidClientId;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingSapiScope;
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

    /**
     * @var Auth0Management
     */
    private $auth0Management;

    public function __construct(Container $container, ICultureFeed $cultureFeed, Auth0Management $auth0Management)
    {
        $this->container = $container;
        $this->cultureFeed = $cultureFeed;
        $this->auth0Management = $auth0Management;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method')) {
            return $handler->handle($request);
        }

        $clientId = $this->getClientId($request);

        if ($clientId !== null) {
            return $this->handleClientId($request, $handler, $clientId);
        }

        $apiKey = $this->getApiKey($request);

        if (empty($apiKey)) {
            return (new MissingCredentials())->toResponse();
        }

        return $this->handleApiKey($request, $handler, $apiKey);
    }

    private function handleClientId(ServerRequestInterface $request, RequestHandlerInterface $handler, string $clientId): ResponseInterface
    {
        try {
            $client = $this->auth0Management->clients()->get($clientId, ['client_id', 'client_metadata'], true);
        } catch (Exception $exception) {
            return (new InvalidClientId($clientId))->toResponse();
        }

        if (empty($client['client_metadata']['sapi3'])) {
            return (new MissingSapiScope($clientId))->toResponse();
        }

        $this->container
            ->extend(Consumer::class)
            ->setConcrete(new Consumer($clientId, null));

        return $handler->handle($request);
    }

    private function handleApiKey(ServerRequestInterface $request, RequestHandlerInterface $handler, string $apiKey): ResponseInterface
    {
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

        $this->container
            ->extend(Consumer::class)
            ->setConcrete(
                new Consumer(
                    $apiKey,
                    empty($cultureFeedConsumer->searchPrefixSapi3) ? null : $cultureFeedConsumer->searchPrefixSapi3
                )
            );

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

    private function getClientId(ServerRequestInterface $request): ?string
    {
        if ($this->getHeaderValue($request, 'x-client-id')) {
            return $this->getHeaderValue($request, 'x-client-id');
        }

        if ($this->getParamValue($request, 'clientId')) {
            return $this->getParamValue($request, 'clientId');
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

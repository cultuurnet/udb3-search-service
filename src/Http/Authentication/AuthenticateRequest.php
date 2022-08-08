<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidClientId;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\NotAllowedToUseSapi;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\RemovedApiKey;
use CultuurNet\UDB3\Search\Http\DefaultQuery\DefaultQueryRepository;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use ICultureFeed;
use League\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class AuthenticateRequest implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Container $container;

    private ICultureFeed $cultureFeed;

    private Auth0TokenProvider $auth0TokenProvider;

    private Auth0Client $auth0Client;

    private DefaultQueryRepository $defaultQueryRepository;

    public function __construct(
        Container $container,
        ICultureFeed $cultureFeed,
        Auth0TokenProvider $auth0TokenProvider,
        Auth0Client $auth0Client,
        DefaultQueryRepository $defaultQueryRepository
    ) {
        $this->container = $container;
        $this->cultureFeed = $cultureFeed;
        $this->auth0TokenProvider = $auth0TokenProvider;
        $this->auth0Client = $auth0Client;
        $this->defaultQueryRepository = $defaultQueryRepository;
        $this->setLogger(new NullLogger());
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
        $auth0Down = false;
        $metadata = [];

        try {
            $metadata = $this->auth0Client->getMetadata($clientId, $this->auth0TokenProvider->get()->getToken());

            if ($metadata === null) {
                return (new InvalidClientId($clientId))->toResponse();
            }
        } catch (ConnectException $connectException) {
            $this->logger->error('Auth0 was detected as down, this results in disabling authentication');
            $auth0Down = true;
        }

        // Bypass the sapi access validation when Auth0 is down to make sure sapi requests are still handled.
        if (!$auth0Down && !$this->hasSapiAccess($metadata)) {
            return (new NotAllowedToUseSapi($clientId))->toResponse();
        }

        $this->container
            ->extend(Consumer::class)
            ->setConcrete(new Consumer($clientId, null));

        return $handler->handle($request);
    }

    private function handleApiKey(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        string $apiKey
    ): ResponseInterface {
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

        $defaultQuery = $this->defaultQueryRepository->getByApiKey($apiKey);
        if ($defaultQuery === null && !empty($cultureFeedConsumer->searchPrefixSapi3)) {
            $defaultQuery = $cultureFeedConsumer->searchPrefixSapi3;
        }

        $this->container
            ->extend(Consumer::class)
            ->setConcrete(
                new Consumer(
                    $apiKey,
                    $defaultQuery
                )
            );

        return $handler->handle($request);
    }

    private function hasSapiAccess(array $metadata): bool
    {
        if (empty($metadata)) {
            return false;
        }

        if (empty($metadata['publiq-apis'])) {
            return false;
        }

        $apis = explode(' ', $metadata['publiq-apis']);
        return in_array('sapi', $apis, true);
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

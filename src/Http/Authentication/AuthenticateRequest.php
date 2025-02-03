<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultureFeed_Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\Access\ClientIdProvider;
use CultuurNet\UDB3\Search\Http\Authentication\Access\InvalidClient;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\BlockedApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidClientId;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidToken;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\MissingCredentials;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\NotAllowedToUseSapi;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\RemovedApiKey;
use CultuurNet\UDB3\Search\Http\DefaultQuery\DefaultQueryRepository;
use CultuurNet\UDB3\Search\LoggerAwareTrait;
use Exception;
use ICultureFeed;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use League\Container\Container;
use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

final class AuthenticateRequest implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const BEARER = 'Bearer ';

    private Container $container;

    private ConsumerProvider $consumerProvider;

    private ClientIdProvider $clientIdProvider;

    private DefaultQueryRepository $defaultQueryRepository;

    private string $pemFile;

    public function __construct(
        Container $container,
        ConsumerProvider $consumerProvider,
        ClientIdProvider $clientIdProvider,
        DefaultQueryRepository $defaultQueryRepository,
        string $pemFile
    ) {
        $this->container = $container;
        $this->consumerProvider = $consumerProvider;
        $this->clientIdProvider = $clientIdProvider;
        $this->defaultQueryRepository = $defaultQueryRepository;
        $this->pemFile = $pemFile;
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

        if ($apiKey !== null) {
            return $this->handleApiKey($request, $handler, $apiKey);
        }

        $accessToken = $this->getAccessToken($request);

        if ($accessToken !== null) {
            return $this->handleAccessToken($request, $handler, $accessToken);
        }

        return (new MissingCredentials())->toResponse();
    }

    private function handleClientId(ServerRequestInterface $request, RequestHandlerInterface $handler, string $clientId): ResponseInterface
    {
        try {
            $hasSapiAccess = $this->clientIdProvider->hasSapiAccess($clientId);
        } catch (InvalidClient $invalidClient) {
            return (new InvalidClientId($clientId))->toResponse();
        }

        if (!$hasSapiAccess) {
            return (new NotAllowedToUseSapi($clientId))->toResponse();
        }

        $defaultQuery = $this->defaultQueryRepository->getByClientId($clientId);

        $this->container
            ->extend(Consumer::class)
            ->setConcrete(new Consumer($clientId, $defaultQuery));

        return $handler->handle($request);
    }

    private function handleAccessToken(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        string $accessToken
    ): ResponseInterface {
        if (strpos($accessToken, self::BEARER) !== 0) {
            return (
                new InvalidToken('Authorization header must start with "' . self::BEARER . '", followed by your token')
            )->toResponse();
        }

        $tokenString = substr($accessToken, strlen(self::BEARER));

        try {
            $token = new JsonWebToken($tokenString);
        } catch (InvalidTokenStructure $exception) {
            return (new InvalidToken('Token "' . $tokenString . '" is not a valid JWT.'))->toResponse();
        }

        if (!$token->validate($this->pemFile)) {
            return (new InvalidToken('Token "' . $tokenString . '" is expired or not valid for Search API.'))->toResponse();
        }

        $config = $this->container->get(Config::class);
        $jwtUrl = $config->get('jwt.domain');
        if (!$token->isAllowedOnSearchApi($jwtUrl)) {
            return (new NotAllowedToUseSapi())->toResponse();
        }

        return $handler->handle($request);
    }

    private function getAccessToken(ServerRequestInterface $request): ?string
    {
        if ($this->getHeaderValue($request, 'authorization')) {
            return $this->getHeaderValue($request, 'authorization');
        }

        return null;
    }

    private function handleApiKey(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        string $apiKey
    ): ResponseInterface {
        $status = $this->consumerProvider->getStatus($apiKey);

        if ($status === 'INVALID') {
            return (new InvalidApiKey($apiKey))->toResponse();
        }

        if ($status === 'BLOCKED') {
            return (new BlockedApiKey($apiKey))->toResponse();
        }

        if ($status === 'REMOVED') {
            return (new RemovedApiKey($apiKey))->toResponse();
        }

        $defaultQuery = $this->defaultQueryRepository->getByApiKey($apiKey);
        if ($defaultQuery === null && !empty($this->consumerProvider->getDefaultQuery($apiKey))) {
            $defaultQuery = $this->consumerProvider->getDefaultQuery($apiKey);
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

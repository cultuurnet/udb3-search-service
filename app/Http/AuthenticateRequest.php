<?php

namespace CultuurNet\UDB3\SearchService\Http;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticationException;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticatorInterface;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\SearchService\ApiKey\ApiKeyReaderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticateRequest implements MiddlewareInterface
{
    /**
     * @var ApiKeyReaderInterface
     */
    private $apiKeyReader;

    /**
     * @var ApiKeyAuthenticatorInterface
     */
    private $apiKeyAuthenticator;

    public function __construct(ApiKeyReaderInterface $apiKeyReader, ApiKeyAuthenticatorInterface $apiKeyAuthenticator)
    {
        $this->apiKeyReader = $apiKeyReader;
        $this->apiKeyAuthenticator = $apiKeyAuthenticator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === "OPTIONS" && $request->hasHeader("Access-Control-Request-Method")) {
            return;
        }

        $apiKey = $this->apiKeyReader->read($request);

        if (is_null($apiKey)) {
            throw new RequestAuthenticationException('No API key provided.');
        }

        try {
            $this->apiKeyAuthenticator->authenticate($apiKey);
        } catch (ApiKeyAuthenticationException $e) {
            throw new RequestAuthenticationException("Invalid API key provided ({$apiKey->toNative()}).");
        }

        return $handler->handle($request);
    }
}

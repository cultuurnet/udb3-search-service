<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Http;

use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use CultuurNet\UDB3\Search\Http\ApiRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticateRequest implements MiddlewareInterface
{
    /**
     * @var ApiKeyRequestAuthenticator
     */
    private $apiKeyRequestAuthenticator;

    public function __construct(ApiKeyRequestAuthenticator $apiKeyRequestAuthenticator)
    {
        $this->apiKeyRequestAuthenticator = $apiKeyRequestAuthenticator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = new ApiRequest($request);

        if ($request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method')) {
            return $handler->handle($request);
        }

        $this->apiKeyRequestAuthenticator->authenticate($request);

        return $handler->handle($request);
    }
}

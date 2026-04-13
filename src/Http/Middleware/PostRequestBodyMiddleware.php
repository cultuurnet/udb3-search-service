<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Middleware;

use CultuurNet\UDB3\Search\Http\Middleware\ApiProblems\BadRequest;
use CultuurNet\UDB3\Search\Http\Middleware\ApiProblems\UnsupportedMediaType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PostRequestBodyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return $handler->handle($request);
        }

        if ($this->hasNonAuthenticationParams($request->getQueryParams())) {
            return (
                new BadRequest('POST requests do not allow query parameters in the URL. Use the request body instead.')
            )->toResponse();
        }

        $contentType = $request->getHeaderLine('Content-Type');

        if (stripos($contentType, 'text/plain') === false) {
            return (new UnsupportedMediaType())->toResponse();
        }

        $body = (string) $request->getBody();

        $queryParams = [];
        parse_str($body, $queryParams);

        $request = $request->withQueryParams($queryParams);

        return $handler->handle($request);
    }

    private function hasNonAuthenticationParams(array $queryParams): bool
    {
        $nonAuthenticationParams = array_filter(
            $queryParams,
            fn (string $key) => !in_array($key, ['clientId', 'apiKey']),
            ARRAY_FILTER_USE_KEY
        );
        return !empty($nonAuthenticationParams);
    }
}

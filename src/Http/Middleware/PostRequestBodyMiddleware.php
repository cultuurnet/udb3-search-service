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

        if (!empty($this->stripAuthenticationParams($request->getQueryParams()))) {
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

    private function stripAuthenticationParams(array $queryParams): array
    {
        // Use array_diff_key to create a diff between the given query params (keys) and the authentication- 
        // specific query params (keys). The result is either an empty list if no other query params are
        // included in the request, or a list of (unsupported) query params.
        // Since array_diff_key creates a diff based on array keys, we use array_flip to create an array with
        // authentication-specific keys like clientId and apiKey.
        // E.g. ['clientId', 'apiKey'] becomes ['clientId' => 0, 'apiKey' => 1] 
        // (the values don't really matter in this case)
        return array_diff_key(
            $queryParams,
            array_flip(['clientId', 'apiKey'])
        );
    }
}

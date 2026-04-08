<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Middleware;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Fig\Http\Message\StatusCodeInterface;
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

        $contentType = $request->getHeaderLine('Content-Type');

        if (stripos($contentType, 'text/plain') === false) {
            $apiProblem = new ApiProblem('Unsupported Media Type', 'https://api.publiq.be/probs/body/unsupported-media-type');
            $apiProblem->setStatus(StatusCodeInterface::STATUS_UNSUPPORTED_MEDIA_TYPE);
            $apiProblem->setDetail('POST requests require Content-Type text/plain.');

            return ResponseFactory::apiProblem(
                $apiProblem->asArray(),
                $apiProblem->getStatus()
            );
        }

        $body = (string) $request->getBody();

        $queryParams = [];
        parse_str($body, $queryParams);

        $request = $request->withQueryParams($queryParams);

        return $handler->handle($request);
    }
}

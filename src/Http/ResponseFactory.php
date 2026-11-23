<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Json;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

final class ResponseFactory
{
    // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
    private const JSON_OPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * @param array|object $data
     */
    public static function jsonLd($data, int $code = StatusCodeInterface::STATUS_OK): ResponseInterface
    {
        return self::jsonWithCustomContentType('application/ld+json', $data, $code);
    }

    public static function text(string $text): ResponseInterface
    {
        return self::withCustomContentType('text/plain; charset=utf-8', $text);
    }

    public static function html(string $html): ResponseInterface
    {
        return self::withCustomContentType('text/html; charset=utf-8', $html);
    }

    /**
     * @param array|object $data
     */
    public static function apiProblem($data, int $code = StatusCodeInterface::STATUS_OK): ResponseInterface
    {
        return self::jsonWithCustomContentType('application/problem+json', $data, $code);
    }

    /**
     * @param array|object $data
     */
    private static function jsonWithCustomContentType(
        string $contentType,
        $data,
        int $code = StatusCodeInterface::STATUS_OK
    ): ResponseInterface {
        return self::withCustomContentType($contentType, Json::encodeWithOptions($data, self::JSON_OPTIONS), $code);
    }

    private static function withCustomContentType(
        string $contentType,
        string $body,
        int $code = StatusCodeInterface::STATUS_OK
    ): ResponseInterface {
        $response = (new Response($code))->withAddedHeader('Content-Type', $contentType);
        $response->getBody()->write($body);
        return $response;
    }
}

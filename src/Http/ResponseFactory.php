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
        $response = new Response($code);

        $response = $response->withAddedHeader('Content-Type', $contentType);

        $body = $response->getBody();
        $body->write(Json::encodeWithOptions($data, self::JSON_OPTIONS));

        return $response->withBody($body);
    }
}

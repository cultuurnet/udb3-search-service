<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ResponseFactory
{
    // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
    private const JSON_OPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * @param array|object $data
     * @param int $code
     * @return ResponseInterface
     */
    public static function jsonLd($data, int $code = StatusCodeInterface::STATUS_OK) : ResponseInterface
    {
        $response = new Response($code);

        $response = $response->withAddedHeader('Content-Type', 'application/ld+json');

        $body = $response->getBody();
        $body->write(json_encode($data, self::JSON_OPTIONS));

        return $response->withBody($body);
    }
}

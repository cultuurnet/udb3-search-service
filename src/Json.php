<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use JsonException;

final class Json
{
    public static int $depth = 512;

    /**
     * @param mixed $value
     *   Data to encode as JSON, usually an array or stdClass object
     *
     * @return string
     *   Encoded JSON.
     *
     * @throws JsonException
     *   If the JSON could not be encoded, for example because of too much nesting.
     */
    public static function encode($value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR, self::$depth);
    }

    /**
     * @param mixed $value
     *   Data to encode as JSON, usually an array or stdClass object
     *
     * @param int $options
     *   Extra encoding options
     *
     * @return string
     *   Encoded JSON.
     *
     * @throws JsonException
     *   If the JSON could not be encoded, for example because of too much nesting.
     */
    public static function encodeWithOptions($value, int $options): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | $options, self::$depth);
    }

    /**
     * @param mixed $value
     *   Data to encode as JSON for a HTTP response
     *
     * @return string
     *   Encoded JSON.
     *
     * @throws JsonException
     *   If the JSON could not be encoded, for example because of too much nesting.
     */
    public static function encodeForHttpResponse($value): string
    {
        // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
        $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

        return json_encode($value, $options, self::$depth);
    }

    /**
     * @param string $data
     *   Encoded JSON data.
     *
     * @returns mixed
     *   Decoded data, usually as an array or stdClass object but can also be a string, integer, boolean, etc depending
     *   on the encoded data.
     *
     * @throws JsonException
     *   If the JSON could not be decoded, for example because the syntax is invalid.
     */
    public static function decode(string $data)
    {
        return json_decode($data, false, self::$depth, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $data
     *   Encoded JSON data.
     *
     * @returns mixed
     *   Decoded data, usually as an array but can also be a string, integer, boolean, etc depending on the encoded
     *   data.
     *
     * @throws JsonException
     *   If the JSON could not be decoded, for example because the syntax is invalid.
     */
    public static function decodeAssociatively(string $data)
    {
        return json_decode($data, true, self::$depth, JSON_THROW_ON_ERROR);
    }
}

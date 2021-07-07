<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use InvalidArgumentException;

final class Url
{
    // Important! The ? after .* is required to make the match non-greedy so it doesn't capture the trailing slash
    // before it gets to that check.
    private const NORMALIZATION_REGEX = '/^(https?:\/\/)?(www.)?(.*?)(\/?)$/i';

    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        // Normally any string should match the normalization regex, but just in case check it so we don't run into an
        // error down the line when getNormalizedUrl() gets called.
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match(self::NORMALIZATION_REGEX, $url)) {
            throw new InvalidArgumentException('Url ' . $url . ' is not supported');
        }

        $this->url = $url;
    }

    /**
     * Returns the original URL but without:
     * - http:// or https:// in front
     * - www.
     * - trailing slash
     */
    public function getNormalizedUrl(): string
    {
        // $matches will be filled with 5 values:
        // 0. The full match (i.e. the input string if the regex matches)
        // 1. http:// or https:// or an empty string
        // 2. www. or an empty string
        // 3. The normalized URL (i.e. everything in the middle)
        // 4. Trailing slash or empty string
        $matches = [];
        preg_match(self::NORMALIZATION_REGEX, $this->url, $matches);
        return $matches[3];
    }

    public function getDomain(): string
    {
        $urlParts = parse_url($this->url);

        if (!empty($urlParts['host'])) {
            $host = $urlParts['host'];

            if (strpos($host, 'www.') === 0) {
                return substr($host, strlen('www.'));
            }

            return $host;
        }

        return $this->url;
    }

    public function toString(): string
    {
        return $this->url;
    }
}

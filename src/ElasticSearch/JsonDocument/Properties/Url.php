<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use InvalidArgumentException;

final class Url
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $urlParts;

    public function __construct(string $url)
    {
        $this->urlParts = parse_url($url);

        if (!is_array($this->urlParts) || !isset($this->urlParts['host'])) {
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
        return implode(
            '',
            [
                $this->getDomain(),
                isset($this->urlParts['port']) ? ':' . $this->urlParts['port'] : null,
                isset($this->urlParts['path']) ? rtrim($this->urlParts['path'], '/') : null,
                isset($this->urlParts['query']) ? '?' . $this->urlParts['query'] : null,
                isset($this->urlParts['fragment']) ? '#' . $this->urlParts['fragment'] : null,
            ]
        );
    }

    public function getDomain(): string
    {
        $host = $this->urlParts['host'];

        if (strpos($host, 'www.') === 0) {
            return substr($host, strlen('www.'));
        }

        return $host;
    }

    public function toString(): string
    {
        return $this->url;
    }
}

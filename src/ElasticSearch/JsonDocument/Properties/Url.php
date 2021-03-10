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

    public function __construct(string $url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Url ' . $url . ' is not supported');
        }

        $this->url = $url;
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

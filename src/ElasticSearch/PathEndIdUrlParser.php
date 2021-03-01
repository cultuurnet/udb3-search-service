<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

class PathEndIdUrlParser implements IdUrlParserInterface
{
    /**
     * @param string $url
     * @return string
     */
    public function getIdFromUrl($url)
    {
        // Remove trailing whitespace and slashes.
        $url = rtrim($url, " \t\n\r\0\x0B/");

        // Return everything that comes after the last slash.
        $parts = explode('/', $url);
        return end($parts);
    }
}

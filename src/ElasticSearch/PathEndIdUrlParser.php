<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

final class PathEndIdUrlParser implements IdUrlParserInterface
{
    /**
     * @param string $url
     */
    public function getIdFromUrl($url): string
    {
        // Remove trailing whitespace and slashes.
        $url = rtrim($url, " \t\n\r\0\x0B/");

        // Return everything that comes after the last slash.
        $parts = explode('/', $url);
        return end($parts);
    }
}

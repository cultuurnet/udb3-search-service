<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

interface IdUrlParserInterface
{
    /**
     * @param string $url
     * @return string
     */
    public function getIdFromUrl($url);
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

interface IdUrlParserInterface
{
    public function getIdFromUrl(string $url): string;
}

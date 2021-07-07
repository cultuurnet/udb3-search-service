<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class UrlTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['url'])) {
            return $draft;
        }

        $url = new Url($from['url']);
        $draft['url'] = $url->getNormalizedUrl();
        $draft['domain'] = $url->getDomain();
        return $draft;
    }
}

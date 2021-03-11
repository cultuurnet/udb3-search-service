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

        $draft['url'] = $from['url'];
        $draft['domain'] = (new Url($from['url']))->getDomain();
        return $draft;
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class DescriptionTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (isset($from['description'])) {
            $draft['description'] = $from['description'];
        }
        return $draft;
    }
}

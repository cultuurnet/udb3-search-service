<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class OriginalEncodedJsonLdTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['originalEncodedJsonLd'] = json_encode((object) $from, JSON_UNESCAPED_SLASHES);
        return $draft;
    }
}

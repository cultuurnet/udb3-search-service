<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

class JsonLdEmbeddingJsonTransformer implements JsonTransformer
{
    public function transform(array $original, array $draft = []): array
    {
        $originalJsonLd = json_decode($original['originalEncodedJsonLd'] ?? '{}', true);
        return array_merge($draft, $originalJsonLd);
    }
}

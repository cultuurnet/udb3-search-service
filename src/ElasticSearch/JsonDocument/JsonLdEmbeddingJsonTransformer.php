<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class JsonLdEmbeddingJsonTransformer implements JsonTransformer
{
    public function transform(array $original, array $draft = []): array
    {
        $originalJsonLd = Json::decodeAssociatively($original['originalEncodedJsonLd'] ?? '{}');
        return array_merge($draft, $originalJsonLd);
    }
}

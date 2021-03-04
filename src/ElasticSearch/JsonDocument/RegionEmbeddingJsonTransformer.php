<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class RegionEmbeddingJsonTransformer implements JsonTransformer
{
    public function transform(array $original, array $draft = []): array
    {
        $regions = $original['regions'] ?? [];
        return array_merge($draft, ['regions' => $regions]);
    }
}

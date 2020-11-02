<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

class MetadataTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['metadata'] = [
            'popularity' => $from['metadata']['popularity'] ?? 0,
        ];

        return $draft;
    }
}

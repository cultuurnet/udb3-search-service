<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

class MetadataTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $metadata = $this->getMetadata($from);

        if (empty($metadata)) {
            return $draft;
        }

        $draft['metadata'] = $metadata;

        return $draft;
    }

    private function getMetadata(array $from): array
    {
        if (!isset($from['metadata'])) {
            return [];
        }

        return [
            'popularity' => $from['metadata']['popularity'] ?: 0,
        ];
    }
}

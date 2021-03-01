<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

/**
 * Converts Event, Place and Organizer results to minimal documents that only
 * contain @id and @type.
 * Should be used when returning search results.
 */
class MinimalRequiredInfoJsonTransformer implements JsonTransformer
{
    public function transform(array $original, array $draft = []): array
    {
        $draft['@id'] = $original['@id'] ?? '';
        $draft['@type'] = $original['@type'] ?? '';
        return $draft;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class OriginalEncodedJsonLdTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        unset($from['hash']);
        $draft['originalEncodedJsonLd'] = Json::encodeWithOptions((object)$from, JSON_UNESCAPED_SLASHES);
        return $draft;
    }
}

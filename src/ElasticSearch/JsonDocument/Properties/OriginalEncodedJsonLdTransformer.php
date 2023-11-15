<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class OriginalEncodedJsonLdTransformer implements JsonTransformer
{
    private const CONTRIBUTORS = 'contributors';

    public function transform(array $from, array $draft = []): array
    {
        if (isset($from[self::CONTRIBUTORS])) {
            unset($from[self::CONTRIBUTORS]);
        }

        $draft['originalEncodedJsonLd'] = Json::encodeWithOptions((object)$from, JSON_UNESCAPED_SLASHES);
        return $draft;
    }
}

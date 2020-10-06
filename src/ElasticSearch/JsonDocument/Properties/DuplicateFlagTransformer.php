<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class DuplicateFlagTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['isDuplicate'] = isset($from['duplicateOf']);
        return $draft;
    }
}

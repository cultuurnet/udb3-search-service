<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class MediaObjectsTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['mediaObjectsCount'] = isset($from['mediaObject']) ? count($from['mediaObject']) : 0;
        return $draft;
    }
}

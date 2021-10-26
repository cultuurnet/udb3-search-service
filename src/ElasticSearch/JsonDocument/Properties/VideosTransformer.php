<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class VideosTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['videosCount'] = isset($from['videos']) ? count($from['videos']) : 0;
        return $draft;
    }
}

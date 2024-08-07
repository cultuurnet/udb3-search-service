<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class PerformersTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['performer']) || !is_array($from['performer'])) {
            return $draft;
        }

        $draft['performer_free_text'] = array_map(
            fn (array $performer): array => [
                'name' => $performer['name'],
            ],
            $from['performer']
        );

        return $draft;
    }
}

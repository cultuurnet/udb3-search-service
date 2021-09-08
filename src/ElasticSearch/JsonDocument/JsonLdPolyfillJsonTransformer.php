<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class JsonLdPolyfillJsonTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        // Apply transformations to the draft of the JSON to return, which should be based on the original JSON-LD
        $draft = $this->polyfillSubEventIds($draft);
        $draft = $this->removeInternalProperties($draft);
        return $draft;
    }

    private function polyfillSubEventIds(array $json): array
    {
        if (!isset($json['subEvent']) || !is_array($json['subEvent'])) {
            return $json;
        }

        $json['subEvent'] = array_map(
            function (array $subEvent, int $index) {
                return array_merge(
                    ['id' => $index],
                    $subEvent
                );
            },
            $json['subEvent'],
            range(0, count($json['subEvent']) - 1)
        );

        return $json;
    }

    private function removeInternalProperties(array $json): array
    {
        $internalProperties = ['metadata'];
        return array_diff_key($json, array_flip($internalProperties));
    }
}

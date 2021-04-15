<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class JsonLdPolyfillJsonTransformer implements JsonTransformer
{
    private const DEFAULT_STATUS = 'Available';

    public function transform(array $from, array $draft = []): array
    {
        // Apply transformations to the draft of the JSON to return, which should be based on the original JSON-LD
        $draft = $this->polyfillNewProperties($draft);
        $draft = $this->removeObsoleteProperties($draft);
        $draft = $this->removeInternalProperties($draft);
        return $draft;
    }

    private function polyfillNewProperties(array $json): array
    {
        $json = $this->polyfillStatus($json);
        $json = $this->polyfillSubEventStatus($json);
        $json = $this->polyfillEmbeddedPlaceStatus($json);
        return $json;
    }

    private function polyfillStatus(array $json): array
    {
        // Fixing the previous status format without the type property.
        if (isset($json['status']) && !isset($json['status']['type'])) {
            $json['status'] = [
                'type' => $json['status'],
            ];
        }

        if (!isset($json['status'])) {
            $json['status'] = [
                'type' => self::DEFAULT_STATUS,
            ];
        }

        return $json;
    }

    private function polyfillSubEventStatus(array $json): array
    {
        if (!isset($json['subEvent']) || !is_array($json['subEvent'])) {
            return $json;
        }

        $json['subEvent'] = array_map(
            function (array $subEvent) {
                return array_merge(
                    [
                        'status' => [
                            'type' => self::DEFAULT_STATUS,
                        ],
                    ],
                    $subEvent
                );
            },
            $json['subEvent']
        );

        return $json;
    }

    private function polyfillEmbeddedPlaceStatus(array $json): array
    {
        if (!isset($json['location'])) {
            return $json;
        }

        if (isset($json['location']['status']) && !isset($json['location']['status']['type'])) {
            $json['location']['status'] = [
                'type' => $json['location']['status'],
            ];
        }

        if (!isset($json['location']['status'])) {
            $json['location']['status'] = [
                'type' => self::DEFAULT_STATUS,
            ];
        }

        return $json;
    }

    private function removeObsoleteProperties(array $json): array
    {
        $obsoleteProperties = ['calendarSummary'];
        return array_diff_key($json, array_flip($obsoleteProperties));
    }

    private function removeInternalProperties(array $json): array
    {
        $internalProperties = ['metadata'];
        return array_diff_key($json, array_flip($internalProperties));
    }
}

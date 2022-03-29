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
        $draft = $this->polyfillMediaObjectIds($draft);
        $draft = $this->polyfillImagesProperties($draft);
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

    private function polyfillMediaObjectIds(array $json): array
    {
        if (!isset($json['mediaObject']) || !is_array($json['mediaObject'])) {
            return $json;
        }

        $json['mediaObject'] = array_map(
            static function ($mediaObject) {
                if (is_array($mediaObject) && !isset($mediaObject['id']) && isset($mediaObject['@id'])) {
                    $urlParts = explode('/', $mediaObject['@id']);
                    $id = array_pop($urlParts);
                    $mediaObject['id'] = $id;
                }
                return $mediaObject;
            },
            $json['mediaObject']
        );

        return $json;
    }

    private function polyfillImagesProperties(array $json): array
    {
        if (!isset($json['images']) || !is_array($json['images'])) {
            return $json;
        }

        $json['images'] = array_map(
            static function ($image) {
                if (is_array($image) && !isset($image['inLanguage']) && isset($image['language'])) {
                    $image['inLanguage'] = $image['language'];
                    unset($image['language']);
                }
                if (is_array($image) && !isset($image['@type'])) {
                    $image['@type'] = 'schema:ImageObject';
                }
                return $image;
            },
            $json['images']
        );

        return $json;
    }

    private function removeInternalProperties(array $json): array
    {
        $internalProperties = ['metadata'];
        return array_diff_key($json, array_flip($internalProperties));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class JsonLdPolyfillJsonTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        // Apply transformations to the draft of the JSON to return, which should be based on the original JSON-LD
        $draft = $this->removeInternalProperties($draft);
        return $draft;
    }

    private function removeInternalProperties(array $json): array
    {
        $internalProperties = ['metadata'];
        return array_diff_key($json, array_flip($internalProperties));
    }
}

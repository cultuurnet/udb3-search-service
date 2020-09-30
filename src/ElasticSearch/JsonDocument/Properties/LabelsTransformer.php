<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class LabelsTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $labels = $this->getLabels($from);

        if (!empty($labels)) {
            $draft['labels'] = $labels;
        }
        return $draft;
    }

    private function getLabels(array $from): array
    {
        $labels = $from['labels'] ?? [];
        $hiddenLabels = $from['hiddenLabels'] ?? [];
        return array_merge($labels, $hiddenLabels);
    }
}

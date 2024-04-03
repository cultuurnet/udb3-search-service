<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class LabelsTransformer implements JsonTransformer
{
    /**
     * @var bool
     */
    private $includeLabelsForFreeText;

    public function __construct(bool $includeLabelsForFreeText)
    {
        $this->includeLabelsForFreeText = $includeLabelsForFreeText;
    }

    public function transform(array $from, array $draft = []): array
    {
        $labels = $this->getLabels($from);

        if (!$labels) {
            return $draft;
        }

        $draft['labels'] = $labels;

        if ($this->includeLabelsForFreeText) {
            $draft['labels_free_text'] = $labels;
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

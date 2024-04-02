<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class MetadataTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['metadata'] = [
            'popularity' => $from['metadata']['popularity'] ?? 0,
        ];

        if (isset($from['metadata']['recommendationFor'])) {
            $draft['metadata']['recommendationFor'] =
                $this->transformRecommendationFor($from['metadata']['recommendationFor']);
        }

        return $draft;
    }

    private function transformRecommendationFor(array $recommendationFor): array
    {
        return array_map(
            fn ($recommendation): array => [
                'event' => basename($recommendation['event']),
                'score' => $recommendation['score'],
            ],
            $recommendationFor
        );
    }
}

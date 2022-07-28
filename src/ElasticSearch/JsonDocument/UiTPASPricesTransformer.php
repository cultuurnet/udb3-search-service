<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class UiTPASPricesTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['priceInfo']) || !is_array($from['priceInfo'])) {
            return $draft;
        }

        $draft['priceInfo'] = [];
        foreach ($from['priceInfo'] as $priceInfo) {
            if ($priceInfo['category'] !== 'uitpas') {
                $draft['priceInfo'][] = $priceInfo;
            }
        }

        return $draft;
    }
}

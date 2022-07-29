<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class UiTPASPricesTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($draft['priceInfo']) || !is_array($draft['priceInfo'])) {
            return $draft;
        }

        $priceInfoWithoutUitpas = [];
        foreach ($draft['priceInfo'] as $priceInfo) {
            if (!is_array($priceInfo) || !isset($priceInfo['category'])) {
                return $draft;
            }
            if ($priceInfo['category'] !== 'uitpas') {
                $priceInfoWithoutUitpas[] = $priceInfo;
            }
        }
        $draft['priceInfo'] = $priceInfoWithoutUitpas;

        return $draft;
    }
}

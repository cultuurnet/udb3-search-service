<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class PriceInfoTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['priceInfo']) || !is_array($from['priceInfo'])) {
            return $draft;
        }

        foreach ($from['priceInfo'] as $priceInfo) {
            if ($priceInfo['category'] === 'base') {
                $draft['price'] = $priceInfo['price'];
                break;
            }
        }

        return $draft;
    }
}

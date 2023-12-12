<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class AddUniqueHashToPlaceTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $lang = $from['mainLanguage'] ?? 'nl';

        $parts = [
            $from['name'][$lang] ?? '',
            $from['address'][$lang]['streetAddress'] ?? '',
            $from['address'][$lang]['postalCode'] ?? '',
            $from['address'][$lang]['addressLocality'] ?? '',
            $from['address'][$lang]['addressCountry'] ?? '',
            $from['creator'] ?? '',
        ];

        //we trim both sides of each part, and remove the empty parts
        $value = mb_strtolower(implode('_', array_filter(array_map('trim', $parts))));

        if (!empty($value)) {
            $draft['hash'] = $value;
        }

        return $draft;
    }
}

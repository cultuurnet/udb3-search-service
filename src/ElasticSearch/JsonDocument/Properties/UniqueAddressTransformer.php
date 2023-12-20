<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class UniqueAddressTransformer implements JsonTransformer
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

        $parts = array_map(fn ($part) => str_replace(' ', '_', trim($part)), $parts);
        $value = mb_strtolower(implode('_', array_filter($parts)));

        if (!empty($value)) {
            $draft['unique_address_identifier'] = $value;
        }

        return $draft;
    }
}

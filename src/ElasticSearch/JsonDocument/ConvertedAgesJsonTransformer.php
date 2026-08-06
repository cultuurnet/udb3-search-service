<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

/**
 * Adds typicalAgeRangeConverted and birthdateRangeConverted to a search result. A range that is
 * missing from the original JSON-LD but present on the indexed document was derived at index time,
 * so it is exposed under a "Converted" name to keep it apart from a value the editor entered.
 */
final class ConvertedAgesJsonTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($draft['typicalAgeRange']) && isset($from['typicalAgeRange'])) {
            $draft['typicalAgeRangeConverted'] = self::formatAgeRange($from['typicalAgeRange']);
        }

        if (!isset($draft['birthdateRange']) && isset($from['birthdateRange'])) {
            $birthdateRange = self::formatBirthdateRange($from['birthdateRange']);
            if ($birthdateRange !== null) {
                $draft['birthdateRangeConverted'] = $birthdateRange;
            }
        }

        return $draft;
    }

    private static function formatAgeRange(array $range): string
    {
        return ($range['gte'] ?? '') . '-' . ($range['lte'] ?? '');
    }

    private static function formatBirthdateRange(array $range): ?array
    {
        $from = $range['gte'] ?? null;
        $to = $range['lte'] ?? null;

        // An unbounded range has no from/to pair to expose. The typicalAgeRange still conveys it to
        // the consumer, so the converted birthdate range is left out.
        if ($from === null || $to === null) {
            return null;
        }

        return ['from' => $from, 'to' => $to];
    }
}

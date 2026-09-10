<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

/**
 * Exposes the range that was derived at index time under a "Converted" name, so it can be told apart
 * from an entered one. See the table under "Ages and birthdates" in docs/calendar-indexing.md.
 */
final class ConvertedAgesJsonTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $ageIsDefault = !isset($draft['typicalAgeRange']) || $draft['typicalAgeRange'] === '-';
        if ($ageIsDefault && isset($draft['birthdateRange']) && isset($from['typicalAgeRange'])) {
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

        // An unbounded range has no from/to pair to expose.
        if ($from === null || $to === null) {
            return null;
        }

        return ['from' => $from, 'to' => $to];
    }
}

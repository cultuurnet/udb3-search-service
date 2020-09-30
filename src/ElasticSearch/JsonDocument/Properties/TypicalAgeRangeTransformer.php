<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class TypicalAgeRangeTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['typicalAgeRange']) || !is_string($from['typicalAgeRange'])) {
            return $draft;
        }

        $regexMatches = [];
        preg_match('/(\d*)-(\d*)/', $from['typicalAgeRange'], $regexMatches);


        if (count($regexMatches) !== 3) {
            // The matches should always contain exactly 3 values:
            // 0: The delimiter (-)
            // 1: minAge as string (or empty string)
            // 2: maxAge as string (or empty string)
            return $draft;
        }

        // Be sure to always do a strict comparison here!
        $minAge = ($regexMatches[1] !== '') ? (int) $regexMatches[1] : 0;
        $maxAge = ($regexMatches[2] !== '') ? (int) $regexMatches[2] : null;

        $draft['typicalAgeRange']['gte'] = $minAge;

        if ($maxAge) {
            $draft['typicalAgeRange']['lte'] = $maxAge;
        }

        $draft['allAges'] = ($minAge === 0 && is_null($maxAge));
        return $draft;
    }
}

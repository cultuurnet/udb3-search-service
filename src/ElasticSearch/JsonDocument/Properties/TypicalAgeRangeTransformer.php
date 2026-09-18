<?php

declare(strict_types=1);

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

        if (preg_match('/(\d*)-(\d*)/', $from['typicalAgeRange'], $regexMatches) !== 1) {
            return $draft;
        }

        // Be sure to always do a strict comparison here!
        $minAge = ($regexMatches[1] !== '') ? (int) $regexMatches[1] : 0;
        $maxAge = ($regexMatches[2] !== '') ? (int) $regexMatches[2] : null;

        $draft['typicalAgeRange']['gte'] = $minAge;

        if ($maxAge !== null) {
            $draft['typicalAgeRange']['lte'] = $maxAge;
        }

        $draft['allAges'] = ($minAge === 0 && is_null($maxAge));
        return $draft;
    }
}

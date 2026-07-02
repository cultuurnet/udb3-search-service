<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

/**
 * Derives a birthdateRange from an event's typicalAgeRange so that events
 * expressed through an age range can also be found via birthdateRange queries.
 *
 * The conversion is relative to "now": someone aged [minAge, maxAge] today was
 * born between (now - (maxAge + 1) years + 1 day) and (now - minAge years).
 * Because it depends on the current date, derived ranges drift over time and
 * are only correct as of the moment of indexation.
 *
 * An explicit birthdateRange (already set on the draft by the
 * BirthdateRangeTransformer) always takes precedence and is left untouched.
 */
final class TypicalAgeRangeBirthdateRangeTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (isset($draft['birthdateRange'])) {
            return $draft;
        }

        if (!isset($from['typicalAgeRange']) || !is_string($from['typicalAgeRange'])) {
            return $draft;
        }

        $regexMatches = [];
        preg_match('/^(\d*)-(\d*)$/', $from['typicalAgeRange'], $regexMatches);

        if (count($regexMatches) !== 3) {
            return $draft;
        }

        // Be sure to always do a strict comparison here!
        $minAge = ($regexMatches[1] !== '') ? (int) $regexMatches[1] : 0;
        $maxAge = ($regexMatches[2] !== '') ? (int) $regexMatches[2] : null;

        // An "all ages" event (no lower nor upper bound) is not constrained to a birthdate range.
        if ($minAge === 0 && $maxAge === null) {
            return $draft;
        }

        $now = new Chronos();

        // The youngest person in the range (minAge) was born at most minAge years ago.
        $draft['birthdateRange']['lte'] = $now->modify("-{$minAge} years")->format('Y-m-d');

        // The oldest person in the range (maxAge) was born just over (maxAge + 1) years ago.
        if ($maxAge !== null) {
            $oldest = $maxAge + 1;
            $draft['birthdateRange']['gte'] = $now
                ->modify("-{$oldest} years")
                ->modify('+1 day')
                ->format('Y-m-d');
        }

        return $draft;
    }
}

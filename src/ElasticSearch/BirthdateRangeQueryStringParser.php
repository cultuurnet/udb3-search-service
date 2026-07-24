<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;

/**
 * Finds the birthdate ranges expressed in an advanced (Lucene) query string.
 *
 * It recognises both the flat `birthdateRange:[from TO to]` form and grouped forms such as
 * `birthdateRange:([a TO b] OR [c TO d])`, returning one BirthdateRange per contained date range.
 * The patterns are the single source of truth reused by
 * BirthdateRangeToTypicalAgeRangeQueryStringFactory.
 */
final class BirthdateRangeQueryStringParser
{
    // A birthdateRange clause: flat "[...]" or grouped "(...)".
    public const CLAUSE_PATTERN = '/birthdateRange:(?:\[[^\]]*\]|\([^)]*\))/';

    // A single "[YYYY-MM-DD TO YYYY-MM-DD]" range inside a clause.
    public const DATE_RANGE_PATTERN = '/\[(\d{4}-\d{2}-\d{2}) TO (\d{4}-\d{2}-\d{2})\]/';

    /**
     * @return BirthdateRange[]
     */
    public function parse(string $queryString, DateTimeImmutable $now): array
    {
        if (!preg_match_all(self::CLAUSE_PATTERN, $queryString, $clauseMatches)) {
            return [];
        }

        $ranges = [];
        foreach ($clauseMatches[0] as $clause) {
            preg_match_all(self::DATE_RANGE_PATTERN, $clause, $rangeMatches, PREG_SET_ORDER);

            foreach ($rangeMatches as $rangeMatch) {
                [, $fromString, $toString] = $rangeMatch;

                $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromString);
                $to = DateTimeImmutable::createFromFormat('!Y-m-d', $toString);

                if (!$from instanceof DateTimeImmutable || !$to instanceof DateTimeImmutable) {
                    continue;
                }

                try {
                    $ranges[] = new BirthdateRange($from, $to, $now);
                } catch (UnsupportedParameterValue $e) {
                    // Skip an invalid range (from > to); it cannot match anything.
                    continue;
                }
            }
        }

        return $ranges;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\AbstractQueryString;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\QueryStringFactory;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;

/**
 * Decorates a QueryStringFactory so that an advanced query targeting `birthdateRange`
 * also matches events that only expose an equivalent `typicalAgeRange`.
 *
 * A birthdate range is an absolute date range, while a typical age range is a fixed
 * age range. The birthdate <-> age conversion is relative to "now", so it has to happen
 * at query time rather than at index time (where it would drift as time passes). This
 * mirrors ElasticSearchOfferQueryBuilder::withBirthdateRangeFilter(), which applies the
 * same expansion for the structured birthdateRangeFrom/birthdateRangeTo parameters.
 *
 * "All ages" events are deliberately excluded from the typicalAgeRange match: their range
 * is unbounded and would otherwise match every birthdate query.
 *
 * Both the flat `birthdateRange:[from TO to]` form and grouped forms such as
 * `birthdateRange:([a TO b] OR [c TO d])` are expanded; every date range inside the clause
 * gets its own equivalent typicalAgeRange fallback.
 */
final class BirthdateRangeToTypicalAgeRangeQueryStringFactory implements QueryStringFactory
{
    private QueryStringFactory $decoratedFactory;

    private DateTimeImmutable $now;

    public function __construct(QueryStringFactory $decoratedFactory, ?DateTimeImmutable $now = null)
    {
        $this->decoratedFactory = $decoratedFactory;
        $this->now = $now ?? new Chronos();
    }

    /**
     * @return AbstractQueryString
     */
    public function fromString(string $queryString)
    {
        return $this->decoratedFactory->fromString($this->expandBirthdateRanges($queryString));
    }

    private function expandBirthdateRanges(string $queryString): string
    {
        $expanded = preg_replace_callback(
            BirthdateRangeQueryStringParser::CLAUSE_PATTERN,
            function (array $matches): string {
                $original = $matches[0];

                preg_match_all(BirthdateRangeQueryStringParser::DATE_RANGE_PATTERN, $original, $rangeMatches, PREG_SET_ORDER);

                $ageClauses = [];
                foreach ($rangeMatches as $rangeMatch) {
                    [, $fromString, $toString] = $rangeMatch;

                    $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromString);
                    $to = DateTimeImmutable::createFromFormat('!Y-m-d', $toString);

                    if (!$from instanceof DateTimeImmutable || !$to instanceof DateTimeImmutable) {
                        continue;
                    }

                    try {
                        $range = new BirthdateRange($from, $to, $this->now);
                    } catch (UnsupportedParameterValue $e) {
                        // Skip an invalid range (from > to); ElasticSearch will reject it.
                        continue;
                    }

                    $ageClauses[] = sprintf(
                        '(typicalAgeRange:[%d TO %d] AND NOT allAges:true)',
                        $range->getMinAge(),
                        $range->getMaxAge()
                    );
                }

                if ($ageClauses === []) {
                    return $original;
                }

                // Distinct birthdate ranges can map to the same age range (the conversion is
                // coarse — whole years), so collapse duplicate clauses to keep the query lean.
                $ageClauses = array_unique($ageClauses);

                return sprintf('(%s OR %s)', $original, implode(' OR ', $ageClauses));
            },
            $queryString
        );

        return $expanded ?? $queryString;
    }
}

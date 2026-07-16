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
 * Only the flat `birthdateRange:[from TO to]` form is expanded; grouped forms such as
 * `birthdateRange:([a TO b] OR [c TO d])` are passed through unchanged.
 */
final class BirthdateRangeToTypicalAgeRangeQueryStringFactory implements QueryStringFactory
{
    private const BIRTHDATE_RANGE_PATTERN = '/birthdateRange:\[(\d{4}-\d{2}-\d{2}) TO (\d{4}-\d{2}-\d{2})\]/';

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
            self::BIRTHDATE_RANGE_PATTERN,
            function (array $matches): string {
                [$original, $fromString, $toString] = $matches;

                $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromString);
                $to = DateTimeImmutable::createFromFormat('!Y-m-d', $toString);

                if (!$from instanceof DateTimeImmutable || !$to instanceof DateTimeImmutable) {
                    return $original;
                }

                try {
                    $range = new BirthdateRange($from, $to, $this->now);
                } catch (UnsupportedParameterValue $e) {
                    // Leave an invalid range (from > to) untouched; ElasticSearch will reject it.
                    return $original;
                }

                return sprintf(
                    '(%s OR (typicalAgeRange:[%d TO %d] AND NOT allAges:true))',
                    $original,
                    $range->getMinAge(),
                    $range->getMaxAge()
                );
            },
            $queryString
        );

        return $expanded ?? $queryString;
    }
}

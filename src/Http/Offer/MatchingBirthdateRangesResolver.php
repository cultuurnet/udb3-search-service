<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\BirthdateRangeQueryStringParser;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use stdClass;

/**
 * Resolves the `matchingBirthdateRanges` response field.
 *
 * A request "queries" one or more birthdate ranges, either through the advanced query
 * (`q=birthdateRange:[from TO to]`, possibly grouped or repeated) or through the structured
 * `birthdateRangeFrom`/`birthdateRangeTo` parameters. For every queried range this resolver reports
 * which of the returned events qualify for it, so a consumer can show per-child "geschikt voor"
 * hints when multiple children are searched in one query.
 *
 * The match test mirrors ElasticSearchOfferQueryBuilder::createBirthdateRangeQuery(): an event
 * qualifies when its indexed `birthdateRange` intersects the queried date range, or when its
 * `typicalAgeRange` intersects the equivalent age range (excluding "all ages" events). The
 * birthdate <-> age conversion is relative to "now", matching the query-time behaviour everywhere
 * else in the codebase.
 */
final class MatchingBirthdateRangesResolver
{
    private BirthdateRangeQueryStringParser $queryStringParser;

    private DateTimeImmutable $now;

    public function __construct(BirthdateRangeQueryStringParser $queryStringParser, ?DateTimeImmutable $now = null)
    {
        $this->queryStringParser = $queryStringParser;
        $this->now = $now ?? new Chronos();
    }

    /**
     * The birthdate ranges expressed by the request, in order and without duplicates.
     *
     * @return BirthdateRange[]
     */
    public function queriedRanges(ApiRequestInterface $request): array
    {
        $ranges = [];

        if ($request->hasQueryParam('q')) {
            $ranges = $this->queryStringParser->parse((string) $request->getQueryParam('q'), $this->now);
        }

        $structured = $this->structuredRange($request);
        if ($structured !== null) {
            $ranges[] = $structured;
        }

        return $this->deduplicate($ranges);
    }

    /**
     * @param BirthdateRange[] $queriedRanges
     * @param JsonDocument[] $results
     * @return array<int, array{from: string, to: string, matches: string[]}>
     */
    public function match(array $queriedRanges, array $results): array
    {
        $documents = array_map(static fn (JsonDocument $result): stdClass => $result->getBody(), $results);

        return array_map(
            function (BirthdateRange $range) use ($documents): array {
                $matches = [];
                foreach ($documents as $document) {
                    if ($this->documentMatchesRange($document, $range)) {
                        $matches[] = (string) ($document->{'@id'} ?? '');
                    }
                }

                return [
                    'from' => $range->getFrom()->format('Y-m-d'),
                    'to' => $range->getTo()->format('Y-m-d'),
                    'matches' => $matches,
                ];
            },
            $queriedRanges
        );
    }

    private function structuredRange(ApiRequestInterface $request): ?BirthdateRange
    {
        $parameterBag = $request->getQueryParameterBag();
        $from = $parameterBag->getDateFromParameter('birthdateRangeFrom');
        $to = $parameterBag->getDateFromParameter('birthdateRangeTo');

        if ($from === null || $to === null) {
            return null;
        }

        try {
            return new BirthdateRange($from, $to, $this->now);
        } catch (UnsupportedParameterValue $e) {
            return null;
        }
    }

    /**
     * @param BirthdateRange[] $ranges
     * @return BirthdateRange[]
     */
    private function deduplicate(array $ranges): array
    {
        $seen = [];
        $unique = [];
        foreach ($ranges as $range) {
            $key = $range->getFrom()->format('Y-m-d') . '/' . $range->getTo()->format('Y-m-d');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $range;
        }

        return $unique;
    }

    private function documentMatchesRange(stdClass $document, BirthdateRange $range): bool
    {
        return $this->birthdateRangeMatches($document, $range)
            || $this->typicalAgeRangeMatches($document, $range);
    }

    private function birthdateRangeMatches(stdClass $document, BirthdateRange $range): bool
    {
        $birthdateRange = $document->birthdateRange ?? null;
        if (!$birthdateRange instanceof stdClass) {
            return false;
        }

        $from = isset($birthdateRange->gte) ? DateTimeImmutable::createFromFormat('!Y-m-d', (string) $birthdateRange->gte) : null;
        $to = isset($birthdateRange->lte) ? DateTimeImmutable::createFromFormat('!Y-m-d', (string) $birthdateRange->lte) : null;
        $from = $from instanceof DateTimeImmutable ? $from : null;
        $to = $to instanceof DateTimeImmutable ? $to : null;

        if ($from === null && $to === null) {
            return false;
        }

        // Intersection with [range->from, range->to]; a missing bound is unbounded.
        if ($from !== null && $from > $range->getTo()) {
            return false;
        }
        if ($to !== null && $to < $range->getFrom()) {
            return false;
        }

        return true;
    }

    private function typicalAgeRangeMatches(stdClass $document, BirthdateRange $range): bool
    {
        if (($document->allAges ?? false) === true) {
            return false;
        }

        $typicalAgeRange = $document->typicalAgeRange ?? null;
        if (!$typicalAgeRange instanceof stdClass) {
            return false;
        }

        $min = isset($typicalAgeRange->gte) ? (int) $typicalAgeRange->gte : null;
        $max = isset($typicalAgeRange->lte) ? (int) $typicalAgeRange->lte : null;

        if ($min === null && $max === null) {
            return false;
        }

        // Intersection with [range->minAge, range->maxAge]; a missing bound is unbounded.
        if ($min !== null && $min > $range->getMaxAge()) {
            return false;
        }
        if ($max !== null && $max < $range->getMinAge()) {
            return false;
        }

        return true;
    }
}

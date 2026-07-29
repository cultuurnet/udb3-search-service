<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\BirthdateRangeQueryStringParser;
use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class MatchingBirthdateRangesResolverTest extends TestCase
{
    private MatchingBirthdateRangesResolver $resolver;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        // A fixed "now": born 2020-01-01 is 6, born 2022-12-31 is 3 (age used for typicalAgeRange).
        $this->now = new DateTimeImmutable('2026-07-03');
        $this->resolver = new MatchingBirthdateRangesResolver(
            new BirthdateRangeQueryStringParser(),
            $this->now
        );
    }

    /**
     * @test
     */
    public function it_matches_an_event_whose_birthdate_range_overlaps(): void
    {
        $result = $this->resolver->match(
            [$this->range('2020-01-01', '2022-12-31')],
            [$this->document('event/1', ['birthdateRange' => ['gte' => '2021-01-01', 'lte' => '2021-06-30']])]
        );

        $this->assertSame(
            [['from' => '2020-01-01', 'to' => '2022-12-31', 'matches' => ['event/1']]],
            $result
        );
    }

    /**
     * @test
     */
    public function it_matches_an_event_whose_typical_age_range_overlaps(): void
    {
        // Queried range 2020-01-01 TO 2022-12-31 -> ages 3 TO 6 at the fixed "now".
        $result = $this->resolver->match(
            [$this->range('2020-01-01', '2022-12-31')],
            [$this->document('event/1', ['typicalAgeRange' => ['gte' => 4, 'lte' => 5], 'allAges' => false])]
        );

        $this->assertSame(['event/1'], $result[0]['matches']);
    }

    /**
     * @test
     */
    public function it_excludes_all_ages_events_from_the_age_match(): void
    {
        $result = $this->resolver->match(
            [$this->range('2020-01-01', '2022-12-31')],
            [$this->document('event/1', ['typicalAgeRange' => ['gte' => 0], 'allAges' => true])]
        );

        $this->assertSame([], $result[0]['matches']);
    }

    /**
     * @test
     */
    public function it_does_not_match_a_non_overlapping_event(): void
    {
        $result = $this->resolver->match(
            [$this->range('2020-01-01', '2022-12-31')],
            [
                $this->document('event/1', ['birthdateRange' => ['gte' => '2010-01-01', 'lte' => '2010-12-31']]),
                $this->document('event/2', ['typicalAgeRange' => ['gte' => 20, 'lte' => 30], 'allAges' => false]),
            ]
        );

        $this->assertSame([], $result[0]['matches']);
    }

    /**
     * @test
     */
    public function it_reports_each_queried_range_separately(): void
    {
        $result = $this->resolver->match(
            [
                $this->range('2020-01-01', '2020-12-31'),
                $this->range('2016-01-01', '2018-12-31'),
            ],
            [
                $this->document('event/young', ['birthdateRange' => ['gte' => '2020-06-01', 'lte' => '2020-06-30']]),
                $this->document('event/old', ['birthdateRange' => ['gte' => '2017-01-01', 'lte' => '2017-12-31']]),
            ]
        );

        $this->assertSame(
            [
                ['from' => '2020-01-01', 'to' => '2020-12-31', 'matches' => ['event/young']],
                ['from' => '2016-01-01', 'to' => '2018-12-31', 'matches' => ['event/old']],
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_matches_an_open_ended_typical_age_range(): void
    {
        // typicalAgeRange {gte: 3} means "3 and older"; it overlaps the queried ages 3 TO 6.
        $result = $this->resolver->match(
            [$this->range('2020-01-01', '2022-12-31')],
            [$this->document('event/1', ['typicalAgeRange' => ['gte' => 3], 'allAges' => false])]
        );

        $this->assertSame(['event/1'], $result[0]['matches']);
    }

    /**
     * @test
     */
    public function it_extracts_ranges_from_a_grouped_advanced_query(): void
    {
        $ranges = $this->resolver->queriedRanges(
            $this->requestWithQueryParams([
                'q' => 'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2016-01-01 TO 2018-12-31])',
            ])
        );

        $this->assertSame(
            [['2020-01-01', '2020-12-31'], ['2016-01-01', '2018-12-31']],
            $this->fromToPairs($ranges)
        );
    }

    /**
     * @test
     */
    public function it_extracts_the_structured_birthdate_range_parameters(): void
    {
        $ranges = $this->resolver->queriedRanges(
            $this->requestWithQueryParams([
                'birthdateRangeFrom' => '2018-01-01',
                'birthdateRangeTo' => '2018-12-31',
            ])
        );

        $this->assertSame([['2018-01-01', '2018-12-31']], $this->fromToPairs($ranges));
    }

    /**
     * @test
     */
    public function it_extracts_multiple_structured_birthdate_ranges_from_comma_separated_values(): void
    {
        $ranges = $this->resolver->queriedRanges(
            $this->requestWithQueryParams([
                'birthdateRangeFrom' => '2018-01-01,2020-01-01',
                'birthdateRangeTo' => '2018-12-31,2020-12-31',
            ])
        );

        $this->assertSame(
            [['2018-01-01', '2018-12-31'], ['2020-01-01', '2020-12-31']],
            $this->fromToPairs($ranges)
        );
    }

    /**
     * @test
     */
    public function it_ignores_structured_ranges_when_the_from_and_to_counts_do_not_match(): void
    {
        $ranges = $this->resolver->queriedRanges(
            $this->requestWithQueryParams([
                'birthdateRangeFrom' => '2018-01-01,2020-01-01',
                'birthdateRangeTo' => '2018-12-31',
            ])
        );

        $this->assertSame([], $ranges);
    }

    /**
     * @test
     */
    public function it_deduplicates_identical_ranges_from_both_paths(): void
    {
        $ranges = $this->resolver->queriedRanges(
            $this->requestWithQueryParams([
                'q' => 'birthdateRange:[2018-01-01 TO 2018-12-31]',
                'birthdateRangeFrom' => '2018-01-01',
                'birthdateRangeTo' => '2018-12-31',
            ])
        );

        $this->assertSame([['2018-01-01', '2018-12-31']], $this->fromToPairs($ranges));
    }

    /**
     * @test
     */
    public function it_returns_no_ranges_when_none_are_queried(): void
    {
        $ranges = $this->resolver->queriedRanges(
            $this->requestWithQueryParams(['q' => 'name.nl:foo'])
        );

        $this->assertSame([], $ranges);
    }

    private function range(string $from, string $to): BirthdateRange
    {
        return new BirthdateRange(new DateTimeImmutable($from), new DateTimeImmutable($to), $this->now);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function document(string $id, array $body): JsonDocument
    {
        return new JsonDocument($id, Json::encode(array_merge(['@id' => $id, '@type' => 'Event'], $body)));
    }

    /**
     * @param BirthdateRange[] $ranges
     * @return array<int, array{0: string, 1: string}>
     */
    private function fromToPairs(array $ranges): array
    {
        return array_map(
            static fn (BirthdateRange $range): array => [
                $range->getFrom()->format('Y-m-d'),
                $range->getTo()->format('Y-m-d'),
            ],
            $ranges
        );
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function requestWithQueryParams(array $queryParams): ApiRequest
    {
        return new ApiRequest(
            ServerRequestFactory::createFromGlobals()
                ->withQueryParams($queryParams)
                ->withMethod('GET')
        );
    }
}

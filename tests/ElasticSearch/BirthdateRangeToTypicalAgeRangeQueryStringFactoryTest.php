<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeToTypicalAgeRangeQueryStringFactoryTest extends TestCase
{
    private BirthdateRangeToTypicalAgeRangeQueryStringFactory $factory;

    protected function setUp(): void
    {
        // A fixed "now" keeps the birthdate -> age conversion deterministic:
        // born 2020-01-01 is 6 years old, born 2020-12-31 is 5 years old.
        $this->factory = new BirthdateRangeToTypicalAgeRangeQueryStringFactory(
            new LuceneQueryStringFactory(),
            new DateTimeImmutable('2026-07-03')
        );
    }

    /**
     * @test
     */
    public function it_expands_a_birthdate_range_to_also_match_the_equivalent_typical_age_range(): void
    {
        $actual = $this->factory->fromString('birthdateRange:[2020-01-01 TO 2020-12-31]');

        $expected = new LuceneQueryString(
            '(birthdateRange:[2020-01-01 TO 2020-12-31] OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true))'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_expands_a_birthdate_range_in_a_compound_query(): void
    {
        $actual = $this->factory->fromString(
            'birthdateRange:[2020-01-01 TO 2020-12-31] AND name.nl:foo'
        );

        $expected = new LuceneQueryString(
            '(birthdateRange:[2020-01-01 TO 2020-12-31] OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true))'
            . ' AND name.nl:foo'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_leaves_a_query_without_a_birthdate_range_unchanged(): void
    {
        $actual = $this->factory->fromString('name.nl:foo OR name.nl:bar');

        $expected = new LuceneQueryString('name.nl:foo OR name.nl:bar');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_expands_multiple_separate_birthdate_range_clauses_independently(): void
    {
        $actual = $this->factory->fromString(
            'birthdateRange:[2020-01-01 TO 2020-12-31] OR birthdateRange:[2022-06-30 TO 2022-12-31]'
        );

        $expected = new LuceneQueryString(
            '(birthdateRange:[2020-01-01 TO 2020-12-31] OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true))'
            . ' OR '
            . '(birthdateRange:[2022-06-30 TO 2022-12-31] OR (typicalAgeRange:[3 TO 4] AND NOT allAges:true))'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_expands_a_grouped_birthdate_range_to_also_match_the_equivalent_typical_age_ranges(): void
    {
        $actual = $this->factory->fromString(
            'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-06-30 TO 2022-12-31])'
        );

        $expected = new LuceneQueryString(
            '(birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-06-30 TO 2022-12-31])'
            . ' OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true)'
            . ' OR (typicalAgeRange:[3 TO 4] AND NOT allAges:true))'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_deduplicates_age_clauses_when_ranges_map_to_the_same_age(): void
    {
        // Both ranges resolve to age [5 TO 6] at the fixed "now", so only one age clause is emitted.
        $actual = $this->factory->fromString(
            'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2020-03-01 TO 2020-09-30])'
        );

        $expected = new LuceneQueryString(
            '(birthdateRange:([2020-01-01 TO 2020-12-31] OR [2020-03-01 TO 2020-09-30])'
            . ' OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true))'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_expands_a_grouped_birthdate_range_in_a_compound_query(): void
    {
        $actual = $this->factory->fromString(
            'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-06-30 TO 2022-12-31]) AND name.nl:foo'
        );

        $expected = new LuceneQueryString(
            '(birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-06-30 TO 2022-12-31])'
            . ' OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true)'
            . ' OR (typicalAgeRange:[3 TO 4] AND NOT allAges:true))'
            . ' AND name.nl:foo'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_skips_an_invalid_range_inside_a_grouped_birthdate_range(): void
    {
        $actual = $this->factory->fromString(
            'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-12-31 TO 2022-06-30])'
        );

        // Only the valid range gets an age fallback; the invalid one (from > to) is skipped
        // while the original group is preserved verbatim for ElasticSearch to reject.
        $expected = new LuceneQueryString(
            '(birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-12-31 TO 2022-06-30])'
            . ' OR (typicalAgeRange:[5 TO 6] AND NOT allAges:true))'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_leaves_an_invalid_birthdate_range_unchanged(): void
    {
        $queryString = 'birthdateRange:[2020-12-31 TO 2020-01-01]';

        $actual = $this->factory->fromString($queryString);

        $this->assertEquals(new LuceneQueryString($queryString), $actual);
    }

    /**
     * @test
     */
    public function it_leaves_a_grouped_birthdate_range_without_any_valid_range_unchanged(): void
    {
        $queryString = 'birthdateRange:([2020-12-31 TO 2020-01-01] OR [2022-12-31 TO 2022-06-30])';

        $actual = $this->factory->fromString($queryString);

        $this->assertEquals(new LuceneQueryString($queryString), $actual);
    }
}

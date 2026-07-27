<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeQueryStringParserTest extends TestCase
{
    private BirthdateRangeQueryStringParser $parser;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->parser = new BirthdateRangeQueryStringParser();
        $this->now = new DateTimeImmutable('2026-07-03');
    }

    /**
     * @test
     */
    public function it_parses_a_flat_birthdate_range(): void
    {
        $ranges = $this->parser->parse('birthdateRange:[2020-01-01 TO 2020-12-31]', $this->now);

        $this->assertEquals(
            [$this->range('2020-01-01', '2020-12-31')],
            $ranges
        );
    }

    /**
     * @test
     */
    public function it_parses_every_range_inside_a_grouped_birthdate_range(): void
    {
        $ranges = $this->parser->parse(
            'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-06-30 TO 2022-12-31])',
            $this->now
        );

        $this->assertEquals(
            [
                $this->range('2020-01-01', '2020-12-31'),
                $this->range('2022-06-30', '2022-12-31'),
            ],
            $ranges
        );
    }

    /**
     * @test
     */
    public function it_parses_multiple_separate_birthdate_range_clauses(): void
    {
        $ranges = $this->parser->parse(
            'birthdateRange:[2020-01-01 TO 2020-12-31] OR birthdateRange:[2022-06-30 TO 2022-12-31]',
            $this->now
        );

        $this->assertEquals(
            [
                $this->range('2020-01-01', '2020-12-31'),
                $this->range('2022-06-30', '2022-12-31'),
            ],
            $ranges
        );
    }

    /**
     * @test
     */
    public function it_ignores_date_ranges_that_are_not_part_of_a_birthdate_range_clause(): void
    {
        $ranges = $this->parser->parse(
            'created:[2020-01-01 TO 2020-12-31] AND birthdateRange:[2018-01-01 TO 2018-12-31]',
            $this->now
        );

        $this->assertEquals(
            [$this->range('2018-01-01', '2018-12-31')],
            $ranges
        );
    }

    /**
     * @test
     */
    public function it_returns_no_ranges_for_a_query_without_a_birthdate_range(): void
    {
        $this->assertSame([], $this->parser->parse('name.nl:foo', $this->now));
    }

    /**
     * @test
     */
    public function it_skips_an_invalid_birthdate_range(): void
    {
        $this->assertSame([], $this->parser->parse('birthdateRange:[2020-12-31 TO 2020-01-01]', $this->now));
    }

    private function range(string $from, string $to): BirthdateRange
    {
        return new BirthdateRange(
            new DateTimeImmutable($from),
            new DateTimeImmutable($to),
            $this->now
        );
    }
}

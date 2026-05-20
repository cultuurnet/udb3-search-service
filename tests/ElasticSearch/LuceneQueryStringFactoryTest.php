<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

final class LuceneQueryStringFactoryTest extends TestCase
{
    private LuceneQueryStringFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new LuceneQueryStringFactory();
    }

    /**
     * @test
     */
    public function it_returns_an_instance_of_lucene_query_string(): void
    {
        $queryString = 'foo:bar OR foo:baz';
        $expected = new LuceneQueryString($queryString);
        $actual = $this->factory->fromString($queryString);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_rewrites_type_filter_to_at_type_on_es8(): void
    {
        $actual = $this->factory->fromString('_type:event');
        $expected = new LuceneQueryString('@type:event');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_only_rewrites_the_type_part_in_a_compound_query_on_es8(): void
    {
        $actual = $this->factory->fromString('organizer.id:abc AND _type:event');
        $expected = new LuceneQueryString('organizer.id:abc AND @type:event');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_rewrites_date_range_shorthand_to_lucene_range_syntax(): void
    {
        $actual = $this->factory->fromString('birthdateRange:2020-01-01..2020-12-31');
        $expected = new LuceneQueryString('birthdateRange:[2020-01-01 TO 2020-12-31]');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_distributes_the_field_name_over_an_or_group_of_date_ranges(): void
    {
        // Lucene's query_string parser does not propagate the `field:` prefix to
        // bracketed range expressions inside `(...)`, so we have to repeat the
        // field name on every range or only the first one filters correctly.
        $actual = $this->factory->fromString(
            'birthdateRange:(2020-01-01..2020-12-31 OR 2022-06-30..2022-12-31)'
        );
        $expected = new LuceneQueryString(
            '(birthdateRange:[2020-01-01 TO 2020-12-31] OR birthdateRange:[2022-06-30 TO 2022-12-31])'
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_distributes_the_field_name_over_a_compound_query_with_other_clauses(): void
    {
        $actual = $this->factory->fromString(
            'id:abc AND birthdateRange:(2020-01-01..2020-12-31 OR 2022-06-30..2022-12-31)'
        );
        $expected = new LuceneQueryString(
            'id:abc AND (birthdateRange:[2020-01-01 TO 2020-12-31] OR birthdateRange:[2022-06-30 TO 2022-12-31])'
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_leaves_non_date_double_dot_tokens_alone(): void
    {
        $actual = $this->factory->fromString('name.nl:foo..bar');
        $expected = new LuceneQueryString('name.nl:foo..bar');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_combines_date_range_rewrite_with_type_rewrite(): void
    {
        $actual = $this->factory->fromString(
            '_type:event AND birthdateRange:2020-01-01..2020-12-31'
        );
        $expected = new LuceneQueryString(
            '@type:event AND birthdateRange:[2020-01-01 TO 2020-12-31]'
        );
        $this->assertEquals($expected, $actual);
    }
}

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
        $actual = $this->factory->fromString('birthdateRange:2020-01-01 TO 2020-12-31');
        $expected = new LuceneQueryString('birthdateRange:[2020-01-01 TO 2020-12-31]');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_rewrites_each_date_range_shorthand_inside_an_or_group(): void
    {
        $actual = $this->factory->fromString(
            'birthdateRange:(2020-01-01 TO 2020-12-31 OR 2022-06-30 TO 2022-12-31)'
        );
        $expected = new LuceneQueryString(
            'birthdateRange:([2020-01-01 TO 2020-12-31] OR [2022-06-30 TO 2022-12-31])'
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_leaves_already_bracketed_date_ranges_untouched(): void
    {
        $actual = $this->factory->fromString('birthdateRange:[2020-01-01 TO 2020-12-31]');
        $expected = new LuceneQueryString('birthdateRange:[2020-01-01 TO 2020-12-31]');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_combines_date_range_rewrite_with_type_rewrite(): void
    {
        $actual = $this->factory->fromString(
            '_type:event AND birthdateRange:2020-01-01 TO 2020-12-31'
        );
        $expected = new LuceneQueryString(
            '@type:event AND birthdateRange:[2020-01-01 TO 2020-12-31]'
        );
        $this->assertEquals($expected, $actual);
    }
}

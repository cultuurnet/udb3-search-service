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
     * @dataProvider unchangedQueryProvider
     */
    public function it_does_not_rewrite_fields_that_merely_end_in_type(string $queryString): void
    {
        $actual = $this->factory->fromString($queryString);
        $expected = new LuceneQueryString($queryString);
        $this->assertEquals($expected, $actual);
    }

    public function unchangedQueryProvider(): array
    {
        return [
            'field with a _type suffix' => ['media_type:image'],
            'nested _type field' => ['foo._type:x'],
        ];
    }

    /**
     * @test
     * @dataProvider rewrittenQueryProvider
     */
    public function it_rewrites_type_filters_preceded_by_query_syntax(string $queryString, string $expectedQueryString): void
    {
        $actual = $this->factory->fromString($queryString);
        $expected = new LuceneQueryString($expectedQueryString);
        $this->assertEquals($expected, $actual);
    }

    public function rewrittenQueryProvider(): array
    {
        return [
            'negated' => ['-_type:event', '-@type:event'],
            'grouped' => ['(_type:event OR _type:place)', '(@type:event OR @type:place)'],
        ];
    }
}

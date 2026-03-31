<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

final class LuceneQueryStringFactoryTest extends TestCase
{
    private LuceneQueryStringFactory $factory;

    private LuceneQueryStringFactory $es8Factory;

    protected function setUp(): void
    {
        $this->factory = new LuceneQueryStringFactory();
        $this->factory->enableElasticSearch5CompatibilityMode();
        $this->es8Factory = new LuceneQueryStringFactory();
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
        $actual = $this->es8Factory->fromString('_type:event');
        $expected = new LuceneQueryString('@type:event');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_does_not_rewrite_type_filter_on_es5(): void
    {
        $actual = $this->factory->fromString('_type:event');
        $expected = new LuceneQueryString('_type:event');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_only_rewrites_the_type_part_in_a_compound_query_on_es8(): void
    {
        $actual = $this->es8Factory->fromString('organizer.id:abc AND _type:event');
        $expected = new LuceneQueryString('organizer.id:abc AND @type:event');
        $this->assertEquals($expected, $actual);
    }
}

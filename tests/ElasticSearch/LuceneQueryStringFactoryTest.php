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
}

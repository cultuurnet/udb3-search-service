<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;
use ValueObjects\Exception\InvalidNativeArgumentException;

final class LuceneQueryStringFactoryTest extends TestCase
{
    /**
     * @var LuceneQueryStringFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new LuceneQueryStringFactory();
    }

    /**
     * @test
     */
    public function it_returns_an_instance_of_lucene_query_string()
    {
        $queryString = 'foo:bar OR foo:baz';
        $expected = new LuceneQueryString($queryString);
        $actual = $this->factory->fromString($queryString);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_delegates_validation_to_the_value_object_itself()
    {
        $queryString = false;
        $this->expectException(InvalidNativeArgumentException::class);
        $this->factory->fromString($queryString);
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

class ElasticSearchDistanceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ElasticSearchDistanceFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new ElasticSearchDistanceFactory();
    }

    /**
     * @test
     */
    public function it_returns_an_elastic_search_distance_object()
    {
        $expected = new ElasticSearchDistance('30km');
        $actual = $this->factory->fromString('30km');
        $this->assertEquals($expected, $actual);
    }
}

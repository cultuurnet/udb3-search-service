<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

final class ElasticSearchDistanceFactoryTest extends TestCase
{
    /**
     * @var ElasticSearchDistanceFactory
     */
    private $factory;

    protected function setUp()
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

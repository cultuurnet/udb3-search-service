<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

final class ElasticSearchDistanceFactoryTest extends TestCase
{
    private ElasticSearchDistanceFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ElasticSearchDistanceFactory();
    }

    /**
     * @test
     */
    public function it_returns_an_elastic_search_distance_object(): void
    {
        $expected = new ElasticSearchDistance('30km');
        $actual = $this->factory->fromString('30km');
        $this->assertEquals($expected, $actual);
    }
}

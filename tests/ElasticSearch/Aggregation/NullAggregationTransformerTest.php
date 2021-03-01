<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\TestCase;

class NullAggregationTransformerTest extends TestCase
{
    /**
     * @var NullAggregationTransformer
     */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new NullAggregationTransformer();
    }

    /**
     * @test
     */
    public function it_does_not_support_any_aggregation()
    {
        $aggregation = new Aggregation(FacetName::REGIONS());
        $this->assertFalse($this->transformer->supports($aggregation));
    }

    /**
     * @test
     */
    public function it_always_throws_a_logic_exception_when_trying_to_transform_an_aggregation()
    {
        $aggregation = new Aggregation(FacetName::REGIONS());
        $this->expectException(\LogicException::class);
        $this->transformer->toFacetTree($aggregation);
    }
}

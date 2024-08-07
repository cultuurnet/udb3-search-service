<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\TestCase;

final class NullAggregationTransformerTest extends TestCase
{
    private NullAggregationTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new NullAggregationTransformer();
    }

    /**
     * @test
     */
    public function it_does_not_support_any_aggregation(): void
    {
        $aggregation = new Aggregation(FacetName::regions());
        $this->assertFalse($this->transformer->supports($aggregation));
    }

    /**
     * @test
     */
    public function it_always_throws_a_logic_exception_when_trying_to_transform_an_aggregation(): void
    {
        $aggregation = new Aggregation(FacetName::regions());
        $this->expectException(LogicException::class);
        $this->transformer->toFacetTree($aggregation);
    }
}

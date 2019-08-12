<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\TestCase;

class CompositeAggregationTransformerTest extends TestCase
{
    /**
     * @var AggregationTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transformer1;

    /**
     * @var AggregationTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transformer2;

    /**
     * @var FacetName
     */
    private $aggregationNameSupportedByTransformer1;

    /**
     * @var FacetName
     */
    private $aggregationNameSupportedByTransformer2;

    /**
     * @var FacetName
     */
    private $aggregationNameSupportedByBoth;

    /**
     * @var FacetName
     */
    private $unsupportedAggregationName;

    /**
     * @var CompositeAggregationTransformer
     */
    private $compositeTransformer;

    public function setUp()
    {
        $this->transformer1 = $this->createMock(AggregationTransformerInterface::class);
        $this->transformer2 = $this->createMock(AggregationTransformerInterface::class);

        $this->aggregationNameSupportedByTransformer1 = FacetName::REGIONS();
        $this->aggregationNameSupportedByTransformer2 = FacetName::THEMES();
        $this->aggregationNameSupportedByBoth = FacetName::TYPES();
        $this->unsupportedAggregationName = FacetName::FACILITIES();

        $this->transformer1->expects($this->any())
            ->method('supports')
            ->willReturnCallback(
                function (Aggregation $aggregation) {
                    return $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByTransformer1) ||
                        $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByBoth);
                }
            );

        $this->transformer2->expects($this->any())
            ->method('supports')
            ->willReturnCallback(
                function (Aggregation $aggregation) {
                    return $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByTransformer2) ||
                        $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByBoth);
                }
            );

        $this->compositeTransformer = new CompositeAggregationTransformer();
        $this->compositeTransformer->register($this->transformer1);
        $this->compositeTransformer->register($this->transformer2);
    }

    /**
     * @test
     */
    public function it_supports_any_aggregation_supported_by_at_least_one_registered_transformer()
    {
        $supportedAggregation1 = new Aggregation($this->aggregationNameSupportedByTransformer1);
        $supportedAggregation2 = new Aggregation($this->aggregationNameSupportedByTransformer2);
        $supportedAggregation3 = new Aggregation($this->aggregationNameSupportedByBoth);
        $unsupportedAggregation = new Aggregation($this->unsupportedAggregationName);

        $this->assertTrue($this->compositeTransformer->supports($supportedAggregation1));
        $this->assertTrue($this->compositeTransformer->supports($supportedAggregation2));
        $this->assertTrue($this->compositeTransformer->supports($supportedAggregation3));
        $this->assertFalse($this->compositeTransformer->supports($unsupportedAggregation));
    }

    /**
     * @test
     */
    public function it_delegates_to_the_first_transformer_that_supports_the_aggregation()
    {
        $aggregation = new Aggregation($this->aggregationNameSupportedByBoth);
        $expectedFacetTree = new FacetFilter($this->aggregationNameSupportedByBoth->toNative());

        $this->transformer1->expects($this->once())
            ->method('toFacetTree')
            ->with($aggregation)
            ->willReturn($expectedFacetTree);

        $this->transformer2->expects($this->never())
            ->method('toFacetTree');

        $actualFacetTree = $this->compositeTransformer->toFacetTree($aggregation);

        $this->assertEquals($expectedFacetTree, $actualFacetTree);
    }

    /**
     * @test
     */
    public function it_works_without_any_registered_transformers_but_then_it_does_not_support_any_aggregation_at_all()
    {
        $aggregation = new Aggregation($this->aggregationNameSupportedByBoth);
        $transformer = new CompositeAggregationTransformer();

        $this->assertFalse($transformer->supports($aggregation));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Aggregation "types" not supported for transformation.');
        $transformer->toFacetTree($aggregation);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CompositeAggregationTransformerTest extends TestCase
{
    /**
     * @var AggregationTransformerInterface&MockObject
     */
    private $transformer1;

    /**
     * @var AggregationTransformerInterface&MockObject
     */
    private $transformer2;


    private FacetName $aggregationNameSupportedByTransformer1;


    private FacetName $aggregationNameSupportedByTransformer2;


    private FacetName $aggregationNameSupportedByBoth;


    private FacetName $unsupportedAggregationName;


    private CompositeAggregationTransformer $compositeTransformer;

    protected function setUp(): void
    {
        $this->transformer1 = $this->createMock(AggregationTransformerInterface::class);
        $this->transformer2 = $this->createMock(AggregationTransformerInterface::class);

        $this->aggregationNameSupportedByTransformer1 = FacetName::regions();
        $this->aggregationNameSupportedByTransformer2 = FacetName::themes();
        $this->aggregationNameSupportedByBoth = FacetName::types();
        $this->unsupportedAggregationName = FacetName::facilities();

        $this->transformer1->expects($this->any())
            ->method('supports')
            ->willReturnCallback(
                fn (Aggregation $aggregation): bool => $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByTransformer1) ||
                    $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByBoth)
            );

        $this->transformer2->expects($this->any())
            ->method('supports')
            ->willReturnCallback(
                fn (Aggregation $aggregation): bool => $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByTransformer2) ||
                    $aggregation->getName()->sameValueAs($this->aggregationNameSupportedByBoth)
            );

        $this->compositeTransformer = new CompositeAggregationTransformer();
        $this->compositeTransformer->register($this->transformer1);
        $this->compositeTransformer->register($this->transformer2);
    }

    /**
     * @test
     */
    public function it_supports_any_aggregation_supported_by_at_least_one_registered_transformer(): void
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
    public function it_delegates_to_the_first_transformer_that_supports_the_aggregation(): void
    {
        $aggregation = new Aggregation($this->aggregationNameSupportedByBoth);
        $expectedFacetTree = new FacetFilter($this->aggregationNameSupportedByBoth->toString());

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
    public function it_works_without_any_registered_transformers_but_then_it_does_not_support_any_aggregation_at_all(): void
    {
        $aggregation = new Aggregation($this->aggregationNameSupportedByBoth);
        $transformer = new CompositeAggregationTransformer();

        $this->assertFalse($transformer->supports($aggregation));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Aggregation "types" not supported for transformation.');
        $transformer->toFacetTree($aggregation);
    }
}

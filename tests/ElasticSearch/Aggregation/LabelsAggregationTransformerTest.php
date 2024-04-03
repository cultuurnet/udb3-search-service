<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\TestCase;

final class LabelsAggregationTransformerTest extends TestCase
{
    /**
     * @var FacetName
     */
    private $facetName;

    /**
     * @var LabelsAggregationTransformer
     */
    private $transformer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->facetName = FacetName::labels();

        $this->transformer = new LabelsAggregationTransformer($this->facetName);
    }

    /**
     * @test
     */
    public function it_only_supports_aggregations_named_after_the_injected_facet_name()
    {
        $supported = new Aggregation($this->facetName);
        $unsupported = new Aggregation(FacetName::regions());

        $this->assertTrue($this->transformer->supports($supported));
        $this->assertFalse($this->transformer->supports($unsupported));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Aggregation regions not supported for transformation.');

        $this->transformer->toFacetTree($unsupported);
    }

    /**
     * @test
     */
    public function it_returns_a_facet_filter_solely_based_on_the_bucket_data()
    {
        $aggregation = new Aggregation(
            $this->facetName,
            ...[
                new Bucket('hiddenLabel1', 11),
                new Bucket('hiddenLabel2', 6),
                new Bucket('labelA', 2),
            ]
        );

        $fr = new Language('fr');
        $de = new Language('de');
        $en = new Language('en');

        $expectedFacetTree = new FacetFilter(
            $this->facetName->toString(),
            [
                new FacetNode(
                    'hiddenLabel1',
                    (new MultilingualString(
                        new Language('nl'),
                        'hiddenLabel1'
                    ))
                        ->withTranslation($fr, 'hiddenLabel1')
                        ->withTranslation($de, 'hiddenLabel1')
                        ->withTranslation($en, 'hiddenLabel1'),
                    11
                ),
                new FacetNode(
                    'hiddenLabel2',
                    (new MultilingualString(
                        new Language('nl'),
                        'hiddenLabel2'
                    ))
                        ->withTranslation($fr, 'hiddenLabel2')
                        ->withTranslation($de, 'hiddenLabel2')
                        ->withTranslation($en, 'hiddenLabel2'),
                    6
                ),
                new FacetNode(
                    'labelA',
                    (new MultilingualString(
                        new Language('nl'),
                        'labelA'
                    ))
                        ->withTranslation($fr, 'labelA')
                        ->withTranslation($de, 'labelA')
                        ->withTranslation($en, 'labelA'),
                    2
                ),
            ]
        );

        $this->assertEquals(
            $expectedFacetTree,
            $this->transformer->toFacetTree($aggregation)
        );
    }
}

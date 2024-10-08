<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use InvalidArgumentException;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\TestCase;

final class NodeMapAggregationTransformerTest extends TestCase
{
    private FacetName $facetName;

    private NodeMapAggregationTransformer $transformer;

    protected function setUp(): void
    {
        $this->facetName = FacetName::regions();

        $nodeMap = [
            'prv-vlaams-brabant' => [
                'name' => [
                    'nl' => 'Vlaams-Brabant',
                ],
                'children' => [
                    'gem-leuven' => [
                        'name' => [
                            'nl' => 'Leuven',
                            'fr' => 'Louvain',
                        ],
                        'children' => [
                            'deelgem-leuven' => [
                                'name' => [
                                    'nl' => 'Leuven centrum',
                                    'fr' => 'Louvain central',
                                ],
                            ],
                            'deelgem-wijgmaal' => [
                                'name' => [
                                    'nl' => 'Wijgmaal',
                                    'fr' => 'Louvain nord',
                                ],
                            ],
                            'deelgem-wilsele' => [
                                'name' => [
                                    'nl' => 'Wilsele',
                                ],
                            ],
                            'deelgem-kessel-lo' => [
                                'name' => [
                                    'nl' => 'Kessel-Lo',
                                ],
                            ],
                        ],
                    ],
                    'gem-diest' => [
                        'name' => [
                            'nl' => 'Diest',
                        ],
                    ],
                ],
            ],
            'prv-antwerpen' => [
                'name' => [
                    'nl' => 'Antwerpen',
                    'fr' => 'Anvers',
                ],
            ],
        ];

        $this->transformer = new NodeMapAggregationTransformer(
            $this->facetName,
            $nodeMap
        );
    }

    /**
     * @test
     */
    public function it_only_supports_aggregations_with_the_same_name_as_the_injected_aggregation_name(): void
    {
        $supported = new Aggregation($this->facetName);
        $unsupported = new Aggregation(FacetName::themes());

        $this->assertTrue($this->transformer->supports($supported));
        $this->assertFalse($this->transformer->supports($unsupported));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Aggregation themes not supported for transformation.');

        $this->transformer->toFacetTree($unsupported);
    }

    /**
     * @test
     */
    public function it_returns_a_facet_filter_based_on_the_injected_node_map(): void
    {
        $aggregation = new Aggregation(
            $this->facetName,
            ...[
                new Bucket('prv-vlaams-brabant', 45),
                new Bucket('gem-leuven', 33),
                new Bucket('gem-diest', 12),
                new Bucket('deelgem-leuven', 18),
                new Bucket('deelgem-wijgmaal', 5),
                new Bucket('deelgem-wilsele', 10),
                new Bucket('deelgem-kessel-lo', 0),
            ]
        );

        $expectedFacetTree = new FacetFilter(
            $this->facetName->toString(),
            [
                new FacetNode(
                    'prv-vlaams-brabant',
                    new MultilingualString(
                        new Language('nl'),
                        'Vlaams-Brabant'
                    ),
                    45,
                    [
                        new FacetNode(
                            'gem-leuven',
                            (new MultilingualString(
                                new Language('nl'),
                                'Leuven'
                            ))->withTranslation(new Language('fr'), 'Louvain'),
                            33,
                            [
                                new FacetNode(
                                    'deelgem-leuven',
                                    (new MultilingualString(
                                        new Language('nl'),
                                        'Leuven centrum'
                                    ))->withTranslation(new Language('fr'), 'Louvain central'),
                                    18
                                ),
                                new FacetNode(
                                    'deelgem-wijgmaal',
                                    (new MultilingualString(
                                        new Language('nl'),
                                        'Wijgmaal'
                                    ))->withTranslation(new Language('fr'), 'Louvain nord'),
                                    5
                                ),
                                new FacetNode(
                                    'deelgem-wilsele',
                                    new MultilingualString(
                                        new Language('nl'),
                                        'Wilsele'
                                    ),
                                    10
                                ),
                            ]
                        ),
                        new FacetNode(
                            'gem-diest',
                            new MultilingualString(
                                new Language('nl'),
                                'Diest'
                            ),
                            12
                        ),
                    ]
                ),
            ]
        );

        $actualFacetTree = $this->transformer->toFacetTree($aggregation);

        $this->assertEquals($expectedFacetTree, $actualFacetTree);
    }

    /**
     * @test
     * @dataProvider invalidNodeMapDataProvider
     *
     */
    public function it_validates_the_injected_node_map_upon_construction(
        array $invalidNodeMap,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        new NodeMapAggregationTransformer(FacetName::regions(), $invalidNodeMap);
    }


    public function invalidNodeMapDataProvider(): array
    {
        return [
            'missing_key' => [
                'node_map' => [
                    [
                        'name' => [
                            'nl' => 'Antwerpen',
                        ],
                    ],
                ],
                'exception_message' => 'Facet node 0 has an invalid key.',
            ],

            'missing_name' => [
                'node_map' => [
                    'prv-antwerpen' => [
                    ],
                ],
                'exception_message' => 'Facet node prv-antwerpen has no name.',
            ],

            'invalid_name' => [
                'node_map' => [
                    'prv-antwerpen' => [
                        'name' => 'Antwerpen',
                    ],
                ],
                'exception_message' => 'Facet node prv-antwerpen has a string as name, but it should be an array.',
            ],

            'invalid_language' => [
                'node_map' => [
                    'prv-antwerpen' => [
                        'name' => [
                            'dutch' => 'Antwerpen',
                        ],
                    ],
                ],
                'exception_message' => 'Invalid language code: dutch',
            ],

            'invalid_children' => [
                'node_map' => [
                    'prv-antwerpen' => [
                        'name' => [
                            'nl' => 'Antwerpen',
                        ],
                        'children' => 'gem-berchem',
                    ],
                ],
                'exception_message' => 'Children of facet node prv-antwerpen should be an associative array.',
            ],

            'invalid_child_name' => [
                'node_map' => [
                    'prv-antwerpen' => [
                        'name' => [
                            'nl' => 'Antwerpen',
                        ],
                        'children' => [
                            'gem-antwerpen' => [
                                'name' => 'Antwerpen centrum',
                            ],
                        ],
                    ],
                ],
                'exception_message' => 'Facet node gem-antwerpen has a string as name, but it should be an array.',
            ],
        ];
    }
}

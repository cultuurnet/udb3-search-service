<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NodeMapAggregationTransformer;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchPagedResultSetFactoryTest extends TestCase
{
    /**
     * @var NodeMapAggregationTransformer
     */
    private $aggregationTransformer;

    /**
     * @var ElasticSearchPagedResultSetFactory
     */
    private $factory;

    public function setUp()
    {
        $this->aggregationTransformer = new NodeMapAggregationTransformer(
            FacetName::REGIONS(),
            [
                'gem-leuven' => [
                    'name' => ['nl' => 'Leuven'],
                ],
                'gem-antwerpen' => [
                    'name' => ['nl' => 'Antwerpen'],
                ],
            ]
        );

        $this->factory = new ElasticSearchPagedResultSetFactory(
            $this->aggregationTransformer
        );
    }

    /**
     * @test
     */
    public function it_returns_a_paged_result_set_after_transforming_each_result()
    {
        $response = [
            'hits' => [
                'total' => 962,
                'hits' => [
                    [
                        '_index' => 'udb3-core',
                        '_type' => 'organizer',
                        '_id' => '351b85c1-66ea-463b-82a6-515b7de0d267',
                        '_source' => [
                            '@id' => 'http://foo.bar/organizers/351b85c1-66ea-463b-82a6-515b7de0d267',
                            'name' => 'Collectief Cursief',
                        ],
                    ],
                    [
                        '_index' => 'udb3-core',
                        '_type' => 'organizer',
                        '_id' => 'bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                        '_source' => [
                            '@id' => 'http://foo.bar/organizers/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            'name' => 'Anoniem Collectief',
                        ],
                    ],
                ],
            ],
            'aggregations' => [
                'regions' => [
                    'doc_count_error_upper_bound' => 0,
                    'sum_other_doc_count' => 0,
                    'buckets' => [
                        [
                            'key' => 'gem-leuven',
                            'doc_count' => 10,
                        ],
                        [
                            'key' => 'gem-antwerpen',
                            'doc_count' => 12,
                        ],
                        [
                            'key' => 'gem-brussel',
                            'doc_count' => 5,
                        ],
                    ],
                ],
                // Should be ignored and not be included in the paged result set facets.
                'themes' => [
                    'doc_count_error_upper_bound' => 0,
                    'sum_other_doc_count' => 0,
                    'buckets' => [
                        [
                            'key' => 'bucket1',
                            'doc_count' => 55,
                        ],
                        [
                            'key' => 'bucket2',
                            'doc_count' => 66,
                        ],
                    ],
                ],
            ],
        ];

        $perPage = new Natural(30);

        $expected = new PagedResultSet(
            new Natural(962),
            new Natural(30),
            [
                (new JsonDocument('351b85c1-66ea-463b-82a6-515b7de0d267'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/organizers/351b85c1-66ea-463b-82a6-515b7de0d267',
                            'name' => 'Collectief Cursief',
                        ]
                    ),
                (new JsonDocument('bdc0f4ce-a211-463e-a8d1-d8b699fb1159'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/organizers/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            'name' => 'Anoniem Collectief',
                        ]
                    ),
            ]
        );

        // Note that the gem-brussel node is missing because even though it has
        // a doc_count, it is not present in the node map. Also there's no
        // facet filter for the extra aggregation in the ElasticSearch response
        // because there the injected transformer does not support it.
        $expected = $expected->withFacets(
            new FacetFilter(
                FacetName::REGIONS()->toNative(),
                [
                    new FacetNode(
                        'gem-leuven',
                        new MultilingualString(
                            new Language('nl'),
                            new StringLiteral('Leuven')
                        ),
                        10
                    ),
                    new FacetNode(
                        'gem-antwerpen',
                        new MultilingualString(
                            new Language('nl'),
                            new StringLiteral('Antwerpen')
                        ),
                        12
                    ),
                ]
            )
        );

        $actual = $this->factory->createPagedResultSet($perPage, $response);

        $this->assertEquals($expected, $actual);
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NodeMapAggregationTransformer;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class JsonDocumentTransformingPagedResultSetFactoryTest extends TestCase
{
    /**
     * @var JsonDocumentTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transformer;

    /**
     * @var JsonDocumentTransformingPagedResultSetFactory
     */
    private $factory;

    public function setUp()
    {
        $this->transformer = $this->createMock(JsonDocumentTransformerInterface::class);

        $this->factory = new JsonDocumentTransformingPagedResultSetFactory(
            $this->transformer,
            new ElasticSearchPagedResultSetFactory(
                new NodeMapAggregationTransformer(
                    FacetName::REGIONS(),
                    [
                        'prv-vlaams-brabant' => [
                            'name' => ['nl' => 'Vlaams-Brabant'],
                        ],
                        'prv-antwerpen' => [
                            'name' => ['nl' => 'Antwerpen'],
                        ],
                    ]
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_transforms_each_individual_json_document_before_returning_the_paged_result_set()
    {
        $this->transformer->expects($this->exactly(2))
            ->method('transform')
            ->willReturnCallback(
                function (JsonDocument $jsonDocument) {
                    $body = $jsonDocument->getBody();
                    $body->foo = 'bar';
                    return $jsonDocument->withBody($body);
                }
            );

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
                    'buckets' => [
                        [
                            'key' => 'prv-vlaams-brabant',
                            'doc_count' => 10,
                        ],
                        [
                            'key' => 'prv-antwerpen',
                            'doc_count' => 12,
                        ],
                    ],
                ],
            ],
        ];

        $perPage = new Natural(30);

        $expected = (new PagedResultSet(
            new Natural(962),
            new Natural(30),
            [
                (new JsonDocument('351b85c1-66ea-463b-82a6-515b7de0d267'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/organizers/351b85c1-66ea-463b-82a6-515b7de0d267',
                            'name' => 'Collectief Cursief',
                            'foo' => 'bar',
                        ]
                    ),
                (new JsonDocument('bdc0f4ce-a211-463e-a8d1-d8b699fb1159'))
                    ->withBody(
                        (object) [
                            '@id' => 'http://foo.bar/organizers/bdc0f4ce-a211-463e-a8d1-d8b699fb1159',
                            'name' => 'Anoniem Collectief',
                            'foo' => 'bar',
                        ]
                    ),
            ]
        ))->withFacets(
            new FacetFilter(
                FacetName::REGIONS()->toNative(),
                [
                    new FacetNode(
                        'prv-vlaams-brabant',
                        new MultilingualString(
                            new Language('nl'),
                            new StringLiteral('Vlaams-Brabant')
                        ),
                        10
                    ),
                    new FacetNode(
                        'prv-antwerpen',
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

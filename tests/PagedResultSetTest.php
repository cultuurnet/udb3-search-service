<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class PagedResultSetTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_paged_results_and_metadata(): void
    {
        $total = 1000;
        $perPage = 30;

        $results = [
            (new JsonDocument('123'))
                ->withBody(
                    (object) ['@id' => 'http://acme.com/organizer/123', 'name' => 'STUK']
                ),
            (new JsonDocument('456'))
                ->withBody(
                    (object) ['@id' => 'http://acme.com/organizer/456', 'name' => 'Het Depot']
                ),
        ];

        $pagedResultSet = new PagedResultSet(
            $total,
            $perPage,
            $results
        );

        $this->assertEquals($total, $pagedResultSet->getTotal());
        $this->assertEquals($perPage, $pagedResultSet->getPerPage());
        $this->assertEquals($results, $pagedResultSet->getResults());
    }

    /**
     * @test
     */
    public function it_guards_that_results_are_all_json_documents(): void
    {
        $total = 1000;
        $perPage = 30;

        $results = [
            (new JsonDocument('123'))
                ->withBody(
                    (object) ['@id' => 'http://acme.com/organizer/123', 'name' => 'STUK']
                ),
            'foo',
            'bar',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Results should be an array of JsonDocument objects.');

        new PagedResultSet(
            $total,
            $perPage,
            $results
        );
    }

    /**
     * @test
     */
    public function it_has_an_optional_facets_property(): void
    {
        $total = 1000;
        $perPage = 30;

        $results = [
            (new JsonDocument('123'))
                ->withBody(
                    (object) ['@id' => 'http://acme.com/organizer/123', 'name' => 'STUK']
                ),
            (new JsonDocument('456'))
                ->withBody(
                    (object) ['@id' => 'http://acme.com/organizer/456', 'name' => 'Het Depot']
                ),
        ];

        $facets = [
            new FacetFilter(
                'regions',
                [
                    new FacetNode(
                        'prv-vlaams-brabant',
                        new MultilingualString(
                            new Language('nl'),
                            'Vlaams-Brabant'
                        ),
                        20,
                        [
                            new FacetNode(
                                'gem-leuven',
                                new MultilingualString(
                                    new Language('nl'),
                                    'Leuven'
                                ),
                                15
                            ),
                            new FacetNode(
                                'gem-diest',
                                new MultilingualString(
                                    new Language('nl'),
                                    'Diest'
                                ),
                                5
                            ),
                        ]
                    ),
                    new FacetNode(
                        'prv-antwerpen',
                        new MultilingualString(
                            new Language('nl'),
                            'Antwerpen'
                        ),
                        32,
                        [
                            new FacetNode(
                                'gem-antwerpen',
                                new MultilingualString(
                                    new Language('nl'),
                                    'Antwerpen'
                                ),
                                17
                            ),
                            new FacetNode(
                                'gem-westerlo',
                                new MultilingualString(
                                    new Language('nl'),
                                    'Westerlo'
                                ),
                                15
                            ),
                        ]
                    ),
                ]
            ),
            new FacetFilter(
                'terms',
                [
                    new FacetNode(
                        '0.11.6.5',
                        new MultilingualString(
                            new Language('nl'),
                            'Jeugdhuis of jeugdcentrum'
                        ),
                        7
                    ),
                    new FacetNode(
                        '0.11.6.7',
                        new MultilingualString(
                            new Language('nl'),
                            'Bibliotheek'
                        ),
                        14
                    ),
                ]
            ),
        ];

        $pagedResultSet = new PagedResultSet(
            $total,
            $perPage,
            $results
        );

        $pagedResultSetWithFacets = $pagedResultSet->withFacets(...$facets);

        $this->assertEquals([], $pagedResultSet->getFacets());
        $this->assertEquals($facets, $pagedResultSetWithFacets->getFacets());
    }
}

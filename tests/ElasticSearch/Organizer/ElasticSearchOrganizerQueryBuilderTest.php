<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilderTest;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistance;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Url;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryString;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\Start;

final class ElasticSearchOrganizerQueryBuilderTest extends AbstractElasticSearchQueryBuilderTest
{
    /**
     * @test
     */
    public function it_should_build_a_query_with_pagination_parameters(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_advanced_query(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('foo AND bar')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                        [
                            'query_string' => [
                                'query' => 'foo AND bar',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_free_text_query(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withTextQuery('(foo OR baz) AND bar AND labels:test');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                        [
                            'query_string' => $this->expectedTextQuery(
                                '(foo OR baz) AND bar AND labels\\:test'
                            ),
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_autocomplete_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withAutoCompleteFilter('Collectief Cursief');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 0,
            'size' => 30,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'match_phrase' => [
                                'name.nl.autocomplete' => [
                                    'query' => 'Collectief Cursief',
                                ],
                            ],
                        ],
                    ],
                    'should' => [
                        [
                            'match_phrase' => [
                                'name.nl.autocomplete' => [
                                    'query' => 'Collectief Cursief',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_website_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withWebsiteFilter('http://foo.bar');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 0,
            'size' => 30,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'url' => [
                                    'query' => 'foo.bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_builds_a_query_to_filter_on_the_domain_name(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withDomainFilter('publiq.be');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 0,
            'size' => 30,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'term' => [
                                'domain' => 'publiq.be',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_removes_www_prefix_from_domain_names(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withDomainFilter('www.publiq.be');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 0,
            'size' => 30,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'term' => [
                                'domain' => 'publiq.be',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_multiple_filters(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withAutoCompleteFilter('foo')
            ->withWebsiteFilter('http://foo.bar');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'match_phrase' => [
                                'name.nl.autocomplete' => [
                                    'query' => 'foo',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'url' => [
                                    'query' => 'foo.bar',
                                ],
                            ],
                        ],
                    ],
                    'should' => [
                        [
                            'match_phrase' => [
                                'name.nl.autocomplete' => [
                                    'query' => 'foo',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_postal_code_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withPostalCodeFilter(new PostalCode('3000'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'match' => [
                                            'address.nl.postalCode' => [
                                                'query' => '3000',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.fr.postalCode' => [
                                                'query' => '3000',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.de.postalCode' => [
                                                'query' => '3000',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.en.postalCode' => [
                                                'query' => '3000',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_can_build_a_query_to_filter_on_country(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withAddressCountryFilter(new Country('NL'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'match' => [
                                            'address.nl.addressCountry' => [
                                                'query' => 'NL',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.fr.addressCountry' => [
                                                'query' => 'NL',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.de.addressCountry' => [
                                                'query' => 'NL',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.en.addressCountry' => [
                                                'query' => 'NL',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geoshape_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withRegionFilter(
                'geoshapes',
                'regions',
                new RegionId('gem-leuven')
            )
            ->withRegionFilter(
                'geoshapes',
                'regions',
                new RegionId('prv-limburg')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'geo_shape' => [
                                'geo' => [
                                    'indexed_shape' => [
                                        'index' => 'geoshapes',
                                        'type' => 'regions',
                                        'id' => 'gem-leuven',
                                        'path' => 'location',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'geo_shape' => [
                                'geo' => [
                                    'indexed_shape' => [
                                        'index' => 'geoshapes',
                                        'type' => 'regions',
                                        'id' => 'prv-limburg',
                                        'path' => 'location',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geodistance_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withGeoDistanceFilter(
                new GeoDistanceParameters(
                    new Coordinates(
                        new Latitude(-40.3456),
                        new Longitude(78.3)
                    ),
                    new ElasticSearchDistance('30km')
                )
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'geo_distance' => [
                                'distance' => '30km',
                                'geo_point' => (object) [
                                    'lat' => -40.3456,
                                    'lon' => 78.3,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geo_bounds_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withGeoBoundsFilter(
                new GeoBoundsParameters(
                    new Coordinates(
                        new Latitude(40.73),
                        new Longitude(-71.12)
                    ),
                    new Coordinates(
                        new Latitude(40.01),
                        new Longitude(-74.1)
                    )
                )
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'geo_bounding_box' => [
                                'geo_point' => [
                                    'top_left' => [
                                        'lat' => 40.73,
                                        'lon' => -74.1,
                                    ],
                                    'bottom_right' => [
                                        'lat' => 40.01,
                                        'lon' => -71.12,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_single_facet(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withFacet(
                FacetName::regions()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
            'aggregations' => [
                'regions' => [
                    'terms' => [
                        'field' => 'regions.keyword',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_can_use_a_custom_aggregation_size_for_facets(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder(100))
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withFacet(
                FacetName::regions()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
            'aggregations' => [
                'regions' => [
                    'terms' => [
                        'field' => 'regions.keyword',
                        'size' => 100,
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_creator_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withCreatorFilter(new Creator('John Doe'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'creator' => [
                                    'query' => 'John Doe',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_label_filter(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withLabelFilter(
                new LabelName('foo')
            )
            ->withLabelFilter(
                new LabelName('bar')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'labels' => [
                                    'query' => 'foo',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'labels' => [
                                    'query' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_without_workflow_status_filter_if_no_value_was_given(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withWorkflowStatusFilter();

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_workflow_status_filter_with_a_single_value(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withWorkflowStatusFilter(new WorkflowStatus('ACTIVE'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'workflowStatus' => [
                                    'query' => 'ACTIVE',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_workflow_status_filter_with_multiple_values(): void
    {
        $builder = (new ElasticSearchOrganizerQueryBuilder())
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withWorkflowStatusFilter(
                new WorkflowStatus('ACTIVE'),
                new WorkflowStatus('DELETED')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object) [],
                        ],
                    ],
                    'filter' => [
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'match' => [
                                            'workflowStatus' => [
                                                'query' => 'ACTIVE',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'workflowStatus' => [
                                                'query' => 'DELETED',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_always_return_a_clone_for_each_mutation(): void
    {
        $originalBuilder = new ElasticSearchOrganizerQueryBuilder();

        $mutatedBuilder = $originalBuilder
            ->withStart(new Start(30))
            ->withLimit(new Limit(10))
            ->withAutoCompleteFilter('foo')
            ->withWebsiteFilter('http://foo.bar');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 0,
            'size' => 30,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $originalBuilder->build();
        $mutatedQueryArray = $mutatedBuilder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
        $this->assertNotEquals($expectedQueryArray, $mutatedQueryArray);
    }
}

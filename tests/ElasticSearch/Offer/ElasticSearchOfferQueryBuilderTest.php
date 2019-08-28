<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilderTest;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistance;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryString;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchOfferQueryBuilderTest extends AbstractElasticSearchQueryBuilderTest
{
    protected function getPredefinedQueryStringFields(Language ...$languages)
    {
        if (empty($languages)) {
            $languages = [
                new Language('nl'),
                new Language('fr'),
                new Language('en'),
                new Language('de'),
            ];
        }

        return (new OfferPredefinedQueryStringFields())->getPredefinedFields(...$languages);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_pagination_parameters()
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10));

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_advanced_query()
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAdvancedQuery(
                new LuceneQueryString('foo AND bar')
            );

        $expectedQueryArray = [
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
                                'fields' => $this->getPredefinedQueryStringFields(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_free_text_query()
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withTextQuery(
                new StringLiteral('(foo OR baz) AND bar AND labels:test')
            );

        $expectedQueryArray = [
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
                                '(foo OR baz) AND bar AND labels\\:test',
                                $this->getPredefinedQueryStringFields()
                            ),
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_query_string_query_and_a_subset_of_text_languages()
    {
        $nl = new Language('nl');
        $fr = new Language('fr');

        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAdvancedQuery(
                new LuceneQueryString('foo AND bar'),
                $nl,
                $fr
            );

        $expectedQueryArray = [
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
                                'fields' => $this->getPredefinedQueryStringFields(
                                    $nl,
                                    $fr
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_cdbid_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCdbIdFilter(
                new Cdbid('42926044-09f4-4bd5-bc35-427b2fc1a525')
            );

        $expectedQueryArray = [
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
                                'id' => [
                                    'query' => '42926044-09f4-4bd5-bc35-427b2fc1a525',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_cdbid_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withLocationCdbIdFilter(
                new Cdbid('652ab95e-fdff-41ce-8894-1b29dce0d230')
            );

        $expectedQueryArray = [
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
                                'location.id' => [
                                    'query' => '652ab95e-fdff-41ce-8894-1b29dce0d230',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_organizer_cdbid_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withOrganizerCdbIdFilter(
                new Cdbid('392168d7-57c9-4488-8e2e-d492c843054b')
            );

        $expectedQueryArray = [
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
                                'organizer.id' => [
                                    'query' => '392168d7-57c9-4488-8e2e-d492c843054b',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_without_calendar_type_filter_if_no_value_was_given()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCalendarTypeFilter();

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_calendar_type_filter_with_a_single_value()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCalendarTypeFilter(new CalendarType('single'));

        $expectedQueryArray = [
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
                                'calendarType' => [
                                    'query' => 'single',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_calendar_type_filter_with_multiple_values()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCalendarTypeFilter(
                new CalendarType('SINGLE'),
                new CalendarType('MULTIPLE')
            );

        $expectedQueryArray = [
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
                                            'calendarType' => [
                                                'query' => 'SINGLE',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'calendarType' => [
                                                'query' => 'MULTIPLE',
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_date_range_filter_without_upper_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withDateRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                null
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'dateRange' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_date_range_filter_without_lower_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withDateRangeFilter(
                null,
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'dateRange' => [
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_date_range_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withDateRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'dateRange' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_without_workflow_status_filter_if_no_value_was_given()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withWorkflowStatusFilter();

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_workflow_status_filter_with_a_single_value()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withWorkflowStatusFilter(new WorkflowStatus('DRAFT'));

        $expectedQueryArray = [
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
                                    'query' => 'DRAFT',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_workflow_status_filter_with_multiple_values()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withWorkflowStatusFilter(
                new WorkflowStatus('READY_FOR_VALIDATION'),
                new WorkflowStatus('APPROVED')
            );

        $expectedQueryArray = [
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
                                                'query' => 'READY_FOR_VALIDATION',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'workflowStatus' => [
                                                'query' => 'APPROVED',
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_available_range_filter_without_upper_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAvailableRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                null
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'availableRange' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_available_range_filter_without_lower_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAvailableRangeFilter(
                null,
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'availableRange' => [
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_available_range_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAvailableRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'availableRange' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_an_invalid_available_range()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Start available date should be equal to or smaller than end available date.'
        );

        (new ElasticSearchOfferQueryBuilder())
            ->withAvailableRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00'),
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00')
            );
    }

    /**
     * @test
     */
    public function it_should_ignore_a_range_filter_without_any_lower_or_upper_bounds()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAvailableRangeFilter(
                null,
                null
            );

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geoshape_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withRegionFilter(
                new StringLiteral('geoshapes'),
                new StringLiteral('regions'),
                new RegionId('gem-leuven')
            )
            ->withRegionFilter(
                new StringLiteral('geoshapes'),
                new StringLiteral('regions'),
                new RegionId('prv-limburg')
            );

        $expectedQueryArray = [
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geodistance_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_geo_bounds_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
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
                                    "top_left" => [
                                        'lat' => 40.73,
                                        'lon' => -74.1,
                                    ],
                                    "bottom_right" => [
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_postal_code_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withPostalCodeFilter(new PostalCode("3000"));

        $expectedQueryArray = [
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_country_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAddressCountryFilter(new Country(CountryCode::fromNative("BE")));

        $expectedQueryArray = [
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
                                                'query' => 'BE',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.fr.addressCountry' => [
                                                'query' => 'BE',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.de.addressCountry' => [
                                                'query' => 'BE',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'address.en.addressCountry' => [
                                                'query' => 'BE',
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_age_range_filter_without_upper_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAgeRangeFilter(new Natural(18), null);

        $expectedQueryArray = [
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
                            'range' => [
                                'typicalAgeRange' => [
                                    'gte' => 18,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_age_range_filter_without_lower_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAgeRangeFilter(null, new Natural(18));

        $expectedQueryArray = [
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
                            'range' => [
                                'typicalAgeRange' => [
                                    'lte' => 18,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_age_range_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAgeRangeFilter(new Natural(6), new Natural(12));

        $expectedQueryArray = [
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
                            'range' => [
                                'typicalAgeRange' => [
                                    'gte' => 6,
                                    'lte' => 12,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_inclusive_all_ages_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAgeRangeFilter(new Natural(18), null)
            ->withAllAgesFilter(true);

        $expectedQueryArray = [
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
                            'range' => [
                                'typicalAgeRange' => [
                                    'gte' => 18,
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'allAges' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_exclusive_all_ages_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAgeRangeFilter(new Natural(18), null)
            ->withAllAgesFilter(false);

        $expectedQueryArray = [
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
                            'range' => [
                                'typicalAgeRange' => [
                                    'gte' => 18,
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'allAges' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_price_range_filter_without_upper_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withPriceRangeFilter(Price::fromFloat(9.99), null);

        $expectedQueryArray = [
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
                            'range' => [
                                'price' => [
                                    'gte' => 9.99,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_price_range_filter_without_lower_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withPriceRangeFilter(null, Price::fromFloat(19.99));

        $expectedQueryArray = [
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
                            'range' => [
                                'price' => [
                                    'lte' => 19.99,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_price_range_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withPriceRangeFilter(Price::fromFloat(9.99), Price::fromFloat(19.99));

        $expectedQueryArray = [
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
                            'range' => [
                                'price' => [
                                    'gte' => 9.99,
                                    'lte' => 19.99,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_an_invalid_price_range()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Minimum price should be smaller or equal to maximum price.'
        );

        (new ElasticSearchOfferQueryBuilder())
            ->withPriceRangeFilter(Price::fromFloat(19.99), Price::fromFloat(9.99));
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_audience_type_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withAudienceTypeFilter(new AudienceType('members'));

        $expectedQueryArray = [
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
                                'audienceType' => [
                                    'query' => 'members',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_inclusive_media_objects_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withMediaObjectsFilter(true);

        $expectedQueryArray = [
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
                            'range' => [
                                'mediaObjectsCount' => [
                                    'gte' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_exclusive_media_objects_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withMediaObjectsFilter(false);

        $expectedQueryArray = [
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
                            'range' => [
                                'mediaObjectsCount' => [
                                    'lte' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_inclusive_uitpas_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withUiTPASFilter(true);

        $expectedQueryArray = [
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
                            'query_string' => [
                                'query' => 'organizer.labels:(UiTPAS* OR Paspartoe)',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_exclusive_uitpas_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withUiTPASFilter(false);

        $expectedQueryArray = [
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
                            'query_string' => [
                                'query' => '!(organizer.labels:(UiTPAS* OR Paspartoe))',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_term_id_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withTermIdFilter(
                new TermId('0.12.4.86')
            )
            ->withTermIdFilter(
                new TermId('0.13.4.89')
            );

        $expectedQueryArray = [
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
                                'terms.id' => [
                                    'query' => '0.12.4.86',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'terms.id' => [
                                    'query' => '0.13.4.89',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_term_label_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withTermLabelFilter(
                new TermLabel('Jeugdhuis')
            )
            ->withTermLabelFilter(
                new TermLabel('Cultureel Centrum')
            );

        $expectedQueryArray = [
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
                                'terms.label' => [
                                    'query' => 'Jeugdhuis',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'terms.label' => [
                                    'query' => 'Cultureel Centrum',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_term_id_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withLocationTermIdFilter(
                new TermId('0.12.4.86')
            )
            ->withLocationTermIdFilter(
                new TermId('0.13.4.89')
            );

        $expectedQueryArray = [
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
                                'location.terms.id' => [
                                    'query' => '0.12.4.86',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'location.terms.id' => [
                                    'query' => '0.13.4.89',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_term_label_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withLocationTermLabelFilter(
                new TermLabel('Jeugdhuis')
            )
            ->withLocationTermLabelFilter(
                new TermLabel('Cultureel Centrum')
            );

        $expectedQueryArray = [
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
                                'location.terms.label' => [
                                    'query' => 'Jeugdhuis',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'location.terms.label' => [
                                    'query' => 'Cultureel Centrum',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_label_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withLabelFilter(
                new LabelName('foo')
            )
            ->withLabelFilter(
                new LabelName('bar')
            );

        $expectedQueryArray = [
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_label_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withLocationLabelFilter(
                new LabelName('foo')
            )
            ->withLocationLabelFilter(
                new LabelName('bar')
            );

        $expectedQueryArray = [
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
                                'location.labels' => [
                                    'query' => 'foo',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'location.labels' => [
                                    'query' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_organizer_label_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withOrganizerLabelFilter(
                new LabelName('foo')
            )
            ->withOrganizerLabelFilter(
                new LabelName('bar')
            );

        $expectedQueryArray = [
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
                                'organizer.labels' => [
                                    'query' => 'foo',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'organizer.labels' => [
                                    'query' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_main_language_filter_with_a_single_value()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withMainLanguageFilter(new Language('nl'));

        $expectedQueryArray = [
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
                                'mainLanguage' => [
                                    'query' => 'nl',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_main_language_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withMainLanguageFilter(
                new Language('fr')
            );

        $expectedQueryArray = [
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
                                'mainLanguage' => [
                                    'query' => 'fr',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_language_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withLanguageFilter(
                new Language('fr')
            )
            ->withLanguageFilter(
                new Language('en')
            );

        $expectedQueryArray = [
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
                                'languages' => [
                                    'query' => 'fr',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'languages' => [
                                    'query' => 'en',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_completed_language_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCompletedLanguageFilter(
                new Language('fr')
            )
            ->withCompletedLanguageFilter(
                new Language('en')
            );

        $expectedQueryArray = [
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
                                'completedLanguages' => [
                                    'query' => 'fr',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'completedLanguages' => [
                                    'query' => 'en',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_creator_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCreatorFilter(new Creator('Jane Doe'));

        $expectedQueryArray = [
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
                                    'query' => 'Jane Doe',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_created_range_filter_without_upper_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCreatedRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                null
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'created' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_created_range_filter_without_lower_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCreatedRangeFilter(
                null,
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'created' => [
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_created_range_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withCreatedRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'created' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_modified_range_filter_without_upper_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withModifiedRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                null
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'modified' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_modified_range_filter_without_lower_bound()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withModifiedRangeFilter(
                null,
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'modified' => [
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_modified_range_filter()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withModifiedRangeFilter(
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-04-25T00:00:00+00:00'),
                \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
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
                            'range' => [
                                'modified' => [
                                    'gte' => '2017-04-25T00:00:00+00:00',
                                    'lte' => '2017-05-01T23:59:59+00:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_single_facet()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withFacet(
                FacetName::REGIONS()
            );

        $expectedQueryArray = [
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

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_multiple_facets()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withFacet(
                FacetName::REGIONS()
            )
            ->withFacet(
                FacetName::FACILITIES()
            );

        $expectedQueryArray = [
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
                'facilities' => [
                    'terms' => [
                        'field' => 'facilityIds',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_all_facets()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withFacet(
                FacetName::REGIONS()
            )
            ->withFacet(
                FacetName::TYPES()
            )
            ->withFacet(
                FacetName::THEMES()
            )
            ->withFacet(
                FacetName::FACILITIES()
            );

        $expectedQueryArray = [
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
                'types' => [
                    'terms' => [
                        'field' => 'typeIds',
                    ],
                ],
                'themes' => [
                    'terms' => [
                        'field' => 'themeIds',
                    ],
                ],
                'facilities' => [
                    'terms' => [
                        'field' => 'facilityIds',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_can_use_a_custom_aggregation_size_for_facets()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder(100))
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withFacet(
                FacetName::REGIONS()
            )
            ->withFacet(
                FacetName::FACILITIES()
            );

        $expectedQueryArray = [
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
                'facilities' => [
                    'terms' => [
                        'field' => 'facilityIds',
                        'size' => 100,
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_ignore_unmapped_facets()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withFacet(
                FacetName::REGIONS()
            )
            ->withFacet(
                $this->createUnknownFacetName()
            )
            ->withFacet(
                FacetName::FACILITIES()
            );

        $expectedQueryArray = [
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
                'facilities' => [
                    'terms' => [
                        'field' => 'facilityIds',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_multiple_sorts()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withSortByDistance(
                new Coordinates(
                    new Latitude(8.674),
                    new Longitude(50.23)
                ),
                SortOrder::ASC()
            )
            ->withSortByAvailableTo(SortOrder::ASC())
            ->withSortByScore(SortOrder::DESC());

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
            'sort' => [
                [
                    '_geo_distance' => [
                        'order' => 'asc',
                        'geo_point' => [
                            'lat' => 8.674,
                            'lon' => 50.23,
                        ],
                        'unit' => 'km',
                        'distance_type' => 'plane',
                    ],
                ],
                [
                    'availableTo' => [
                        'order' => 'asc',
                    ],
                ],
                [
                    '_score' => [
                        'order' => 'desc',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_sort_by_created()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withSortByCreated(SortOrder::ASC());

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
            'sort' => [
                [
                    'created' => [
                        'order' => 'asc',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_sort_by_modified()
    {
        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStart(new Natural(30))
            ->withLimit(new Natural(10))
            ->withSortByModified(SortOrder::ASC());

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object) [],
            ],
            'sort' => [
                [
                    'modified' => [
                        'order' => 'asc',
                    ],
                ],
            ],
        ];

        $actualQueryArray = $builder->build()->toArray();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @return FacetName
     */
    private function createUnknownFacetName()
    {
        /** @var FacetName|MockObject $facetName */
        $facetName = $this->createMock(FacetName::class);

        $facetName->method('toNative')
            ->willReturn('unknown');

        return $facetName;
    }
}

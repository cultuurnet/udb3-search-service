<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\DateTimeFactory;
use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilderTest;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistance;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryString;
use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\Age;
use CultuurNet\UDB3\Search\Offer\AttendanceMode;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\Status;
use CultuurNet\UDB3\Search\Offer\SubEventQueryParameters;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use InvalidArgumentException;

final class ElasticSearchOfferQueryBuilderTest extends AbstractElasticSearchQueryBuilderTest
{
    protected function getPredefinedQueryStringFields(Language ...$languages): array
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
    public function it_should_build_a_query_with_pagination_parameters(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
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
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_free_text_query(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withTextQuery('(foo OR baz) AND bar AND labels:test');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_query_string_query_and_a_subset_of_text_languages(): void
    {
        $nl = new Language('nl');
        $fr = new Language('fr');

        /* @var ElasticSearchOfferQueryBuilder $builder */
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAdvancedQuery(
                new LuceneQueryString('foo AND bar'),
                $nl,
                $fr
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_cdbid_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCdbIdFilter(
                new Cdbid('42926044-09f4-4bd5-bc35-427b2fc1a525')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_cdbid_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withLocationCdbIdFilter(
                new Cdbid('652ab95e-fdff-41ce-8894-1b29dce0d230')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_organizer_cdbid_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withOrganizerCdbIdFilter(
                new Cdbid('392168d7-57c9-4488-8e2e-d492c843054b')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_without_calendar_type_filter_if_no_value_was_given(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCalendarTypeFilter();

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_calendar_type_filter_with_a_single_value(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCalendarTypeFilter(new CalendarType('single'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_calendar_type_filter_with_multiple_values(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCalendarTypeFilter(
                new CalendarType('SINGLE'),
                new CalendarType('MULTIPLE')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_date_range_filter_without_upper_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withDateRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_date_range_filter_without_lower_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withDateRangeFilter(
                null,
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_date_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withDateRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_query_with_a_complete_date_range_and_time_range_filter_for_multiple_statuses(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withDateFrom(DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'))
                    ->withDateTo(DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00'))
                    ->withLocalTimeFrom(800)
                    ->withLocalTimeTo(1600)
                    ->withStatuses([Status::temporarilyUnavailable(), Status::unavailable()])
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'subEvent',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'range' => [
                                                    'subEvent.dateRange' => [
                                                        'gte' => '2017-04-25T00:00:00+00:00',
                                                        'lte' => '2017-05-01T23:59:59+00:00',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'range' => [
                                                    'subEvent.localTimeRange' => [
                                                        'gte' => '800',
                                                        'lte' => '1600',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'bool' => [
                                                    'should' => [
                                                        [
                                                            'match' => [
                                                                'subEvent.status' => [
                                                                    'query' => 'TemporarilyUnavailable',
                                                                ],
                                                            ],
                                                        ],
                                                        [
                                                            'match' => [
                                                                'subEvent.status' => [
                                                                    'query' => 'Unavailable',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
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
    public function it_should_build_query_with_a_complete_date_range_and_time_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withDateFrom(DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'))
                    ->withDateTo(DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00'))
                    ->withLocalTimeFrom(800)
                    ->withLocalTimeTo(1600)
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'subEvent',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'range' => [
                                                    'subEvent.dateRange' => [
                                                        'gte' => '2017-04-25T00:00:00+00:00',
                                                        'lte' => '2017-05-01T23:59:59+00:00',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'range' => [
                                                    'subEvent.localTimeRange' => [
                                                        'gte' => '800',
                                                        'lte' => '1600',
                                                    ],
                                                ],
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
    public function it_should_build_query_with_a_complete_date_range_filter_for_multiple_statuses(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withDateFrom(DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'))
                    ->withDateTo(DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00'))
                    ->withStatuses([Status::temporarilyUnavailable(), Status::unavailable()])
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'subEvent',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'range' => [
                                                    'subEvent.dateRange' => [
                                                        'gte' => '2017-04-25T00:00:00+00:00',
                                                        'lte' => '2017-05-01T23:59:59+00:00',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'bool' => [
                                                    'should' => [
                                                        [
                                                            'match' => [
                                                                'subEvent.status' => [
                                                                    'query' => 'TemporarilyUnavailable',
                                                                ],
                                                            ],
                                                        ],
                                                        [
                                                            'match' => [
                                                                'subEvent.status' => [
                                                                    'query' => 'Unavailable',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
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
    public function it_should_build_query_with_a_complete_time_range_filter_for_multiple_statuses(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withLocalTimeFrom(800)
                    ->withLocalTimeTo(1600)
                    ->withStatuses([Status::temporarilyUnavailable(), Status::unavailable()])
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'subEvent',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'range' => [
                                                    'subEvent.localTimeRange' => [
                                                        'gte' => '800',
                                                        'lte' => '1600',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'bool' => [
                                                    'should' => [
                                                        [
                                                            'match' => [
                                                                'subEvent.status' => [
                                                                    'query' => 'TemporarilyUnavailable',
                                                                ],
                                                            ],
                                                        ],
                                                        [
                                                            'match' => [
                                                                'subEvent.status' => [
                                                                    'query' => 'Unavailable',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
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
    public function it_can_build_a_query_with_a_complete_time_range_filter_for_booking_availability(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withLocalTimeFrom(800)
                    ->withLocalTimeTo(1600)
                    ->withBookingAvailability('Unavailable')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'subEvent',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'range' => [
                                                    'subEvent.localTimeRange' => [
                                                        'gte' => '800',
                                                        'lte' => '1600',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'match' => [
                                                    'subEvent.bookingAvailability' => [
                                                        'query' => 'Unavailable',
                                                    ],
                                                ],
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
    public function it_should_build_query_with_a_status_filter_with_multiple_values(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withStatusFilter(
                Status::temporarilyUnavailable(),
                Status::unavailable()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'match' => [
                                            'status' => [
                                                'query' => 'TemporarilyUnavailable',
                                            ],
                                        ],
                                    ],
                                    [
                                        'match' => [
                                            'status' => [
                                                'query' => 'Unavailable',
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
    public function it_builds_a_query_with_with_attendanceMode(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAttendanceModeFilter(
                AttendanceMode::mixed()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'attendanceMode' => [
                                    'query' => 'mixed',
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
    public function it_builds_a_query_with_with_multiple_attendanceMode(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAttendanceModeFilter(
                AttendanceMode::mixed(),
                AttendanceMode::offline()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'match' => [
                                            'attendanceMode' => [
                                                'query' => 'mixed',
                                            ],
                                        ],

                                    ],
                                    [
                                        'match' => [
                                            'attendanceMode' => [
                                                'query' => 'offline',
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
    public function it_can_build_a_query_with_a_booking_availability_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withBookingAvailabilityFilter('Unavailable');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'bookingAvailability' => [
                                    'query' => 'Unavailable',
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
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withWorkflowStatusFilter();

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withWorkflowStatusFilter(new WorkflowStatus('DRAFT'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_workflow_status_filter_with_multiple_values(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withWorkflowStatusFilter(
                new WorkflowStatus('READY_FOR_VALIDATION'),
                new WorkflowStatus('APPROVED')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_available_range_filter_without_upper_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAvailableRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_available_range_filter_without_lower_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAvailableRangeFilter(
                null,
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_available_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAvailableRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_an_invalid_available_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Start available date should be equal to or smaller than end available date.'
        );

        (new ElasticSearchOfferQueryBuilder())
            ->withAvailableRangeFilter(
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00'),
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00')
            );
    }

    /**
     * @test
     */
    public function it_should_ignore_a_range_filter_without_any_lower_or_upper_bounds(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAvailableRangeFilter(
                null,
                null
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
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
                            'match_all' => (object)[],
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
                                    'relation' => 'intersects',
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
                                    'relation' => 'intersects',
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
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
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
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'geo_distance' => [
                                'distance' => '30km',
                                'geo_point' => (object)[
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
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
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
                            'match_all' => (object)[],
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
    public function it_should_build_a_query_with_a_postal_code_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withPostalCodeFilter(new PostalCode('3000'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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
    public function it_should_build_a_query_with_a_country_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAddressCountryFilter(new Country('BE'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_age_range_filter_without_upper_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAgeRangeFilter(new Age(18), null);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_age_range_filter_without_lower_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAgeRangeFilter(null, new Age(18));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_age_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAgeRangeFilter(new Age(6), new Age(12));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_inclusive_all_ages_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAgeRangeFilter(new Age(18), null)
            ->withAllAgesFilter(true);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_exclusive_all_ages_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAgeRangeFilter(new Age(18), null)
            ->withAllAgesFilter(false);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_price_range_filter_without_upper_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withPriceRangeFilter(Price::fromFloat(9.99), null);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_price_range_filter_without_lower_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withPriceRangeFilter(null, Price::fromFloat(19.99));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_price_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withPriceRangeFilter(Price::fromFloat(9.99), Price::fromFloat(19.99));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_an_invalid_price_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Minimum price should be smaller or equal to maximum price.'
        );

        (new ElasticSearchOfferQueryBuilder())
            ->withPriceRangeFilter(Price::fromFloat(19.99), Price::fromFloat(9.99));
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_audience_type_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAudienceTypeFilter(new AudienceType('members'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_inclusive_media_objects_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withMediaObjectsFilter(true);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_exclusive_media_objects_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withMediaObjectsFilter(false);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_inclusive_videos_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withVideosFilter(true);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'range' => [
                                'videosCount' => [
                                    'gte' => 1,
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
    public function it_should_build_a_query_with_an_exclusive_videos_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withVideosFilter(false);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'range' => [
                                'videosCount' => [
                                    'lte' => 0,
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
    public function it_should_build_a_query_with_an_inclusive_uitpas_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withUiTPASFilter(true);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'query_string' => [
                                'query' => 'labels:(UiTPAS* OR Paspartoe)',
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
    public function it_should_build_a_query_with_an_exclusive_uitpas_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withUiTPASFilter(false);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'query_string' => [
                                'query' => '!(labels:(UiTPAS* OR Paspartoe))',
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
    public function it_should_build_a_query_with_a_term_id_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withTermIdFilter(
                new TermId('0.12.4.86')
            )
            ->withTermIdFilter(
                new TermId('0.13.4.89')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_term_label_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withTermLabelFilter(
                new TermLabel('Jeugdhuis')
            )
            ->withTermLabelFilter(
                new TermLabel('Cultureel Centrum')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_term_id_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withLocationTermIdFilter(
                new TermId('0.12.4.86')
            )
            ->withLocationTermIdFilter(
                new TermId('0.13.4.89')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_location_term_label_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withLocationTermLabelFilter(
                new TermLabel('Jeugdhuis')
            )
            ->withLocationTermLabelFilter(
                new TermLabel('Cultureel Centrum')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_label_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
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
                            'match_all' => (object)[],
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
    public function it_should_build_a_query_with_a_location_label_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withLocationLabelFilter(
                new LabelName('foo')
            )
            ->withLocationLabelFilter(
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
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_an_organizer_label_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withOrganizerLabelFilter(
                new LabelName('foo')
            )
            ->withOrganizerLabelFilter(
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
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_main_language_filter_with_a_single_value(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withMainLanguageFilter(new Language('nl'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_main_language_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withMainLanguageFilter(
                new Language('fr')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_language_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withLanguageFilter(
                new Language('fr')
            )
            ->withLanguageFilter(
                new Language('en')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_completed_language_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCompletedLanguageFilter(
                new Language('fr')
            )
            ->withCompletedLanguageFilter(
                new Language('en')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_creator_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCreatorFilter(new Creator('Jane Doe'));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_created_range_filter_without_upper_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCreatedRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_created_range_filter_without_lower_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCreatedRangeFilter(
                null,
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_created_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCreatedRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_modified_range_filter_without_upper_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withModifiedRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
                null
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_modified_range_filter_without_lower_bound(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withModifiedRangeFilter(
                null,
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_complete_modified_range_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withModifiedRangeFilter(
                DateTimeFactory::fromAtom('2017-04-25T00:00:00+00:00'),
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+00:00')
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_a_single_facet(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withFacet(
                FacetName::regions()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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
    public function it_should_build_a_query_with_multiple_facets(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withFacet(
                FacetName::regions()
            )
            ->withFacet(
                FacetName::facilities()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_all_facets(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withFacet(
                FacetName::regions()
            )
            ->withFacet(
                FacetName::types()
            )
            ->withFacet(
                FacetName::themes()
            )
            ->withFacet(
                FacetName::facilities()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_can_use_a_custom_aggregation_size_for_facets(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder(100))
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withFacet(
                FacetName::regions()
            )
            ->withFacet(
                FacetName::facilities()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_query_with_multiple_sorts(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSortByDistance(
                new Coordinates(
                    new Latitude(8.674),
                    new Longitude(50.23)
                ),
                SortOrder::asc()
            )
            ->withSortByAvailableTo(SortOrder::asc())
            ->withSortByScore(SortOrder::desc())
            ->withSortByPopularity(SortOrder::desc())
            ->withSortByRecommendationScore('6f11ca64-0b8b-45e8-8a99-9673f06935cc', SortOrder::asc());

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
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
                [
                    'metadata.popularity' => [
                        'order' => 'desc',
                    ],
                ],
                [
                    'metadata.recommendationFor.score' => [
                        'order' => 'asc',
                        'nested_path' => 'metadata.recommendationFor',
                        'nested' => [
                            'term' => [
                                'metadata.recommendationFor.event' => '6f11ca64-0b8b-45e8-8a99-9673f06935cc',
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
    public function it_should_build_a_query_with_sort_by_created(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSortByCreated(SortOrder::asc());

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
            ],
            'sort' => [
                [
                    'created' => [
                        'order' => 'asc',
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
    public function it_should_build_a_query_with_sort_by_modified(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSortByModified(SortOrder::asc());

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
            ],
            'sort' => [
                [
                    'modified' => [
                        'order' => 'asc',
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
    public function it_should_build_a_query_with_a_contributors_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withContributorsFilter('info@example.com');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'contributors' => [
                                    'query' => 'info@example.com',
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
    public function it_should_build_a_query_with_an_is_duplicate_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withDuplicateFilter(true);

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'term' => [
                                'isDuplicate' => true,
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
    public function it_should_build_a_query_with_a_production_id_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withProductionIdFilter(
                (new Cdbid('652ab95e-fdff-41ce-8894-1b29dce0d230'))->toString()
            );

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'match' => [
                                'production.id' => [
                                    'query' => '652ab95e-fdff-41ce-8894-1b29dce0d230',
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
    public function it_should_build_a_query_with_a_group_by_production_id(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withGroupByProductionId();

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
            ],
            'collapse' => [
                'field' => 'productionCollapseValue',
            ],
            'aggregations' => [
                'total' => [
                    'cardinality' => [
                        'field' => 'productionCollapseValue',
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
    public function it_should_build_a_query_with_a_recommendation_for_filter(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withRecommendationForFilter('652ab95e-fdff-41ce-8894-1b29dce0d230');

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match_all' => (object)[],
                        ],
                    ],
                    'filter' => [
                        [
                            'nested' => [
                                'path' => 'metadata.recommendationFor',
                                'query' => [
                                    'bool' => [
                                        'filter' => [
                                            [
                                                'term' => [
                                                    'metadata.recommendationFor.event' => '652ab95e-fdff-41ce-8894-1b29dce0d230',
                                                ],
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
    public function it_should_accept_a_query_with_start_and_limit_just_under_ten_thousand(): void
    {
        $builder = (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(9980), new Limit(10));

        $expectedQueryArray = [
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
            'from' => 9980,
            'size' => 10,
            'query' => [
                'match_all' => (object)[],
            ],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_throw_if_a_limit_update_pushes_the_result_window_over_ten_thousand(): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(8030), new Limit(1980));
    }

    /**
     * @test
     */
    public function it_should_throw_if_a_start_update_pushes_the_result_window_over_ten_thousand(): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        (new ElasticSearchOfferQueryBuilder())
            ->withStartAndLimit(new Start(9980), new Limit(30));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\DateTimeFactory;
use InvalidArgumentException;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AgeRangeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AvailabilityOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\CalendarOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\CompositeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\ContributorsRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\DistanceOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\DocumentLanguageOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\GroupByOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\IsDuplicateOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\RelatedProductionRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\SortByOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\WorkflowStatusOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Parameters\GeoDistanceParametersFactory;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\Offer\Age;
use CultuurNet\UDB3\Search\Offer\AudienceType;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\Offer\Status;
use CultuurNet\UDB3\Search\Offer\SubEventQueryParameters;
use CultuurNet\UDB3\Search\Offer\TermId;
use CultuurNet\UDB3\Search\Offer\TermLabel;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\PriceInfo\Price;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

final class OfferSearchControllerTest extends TestCase
{
    private MockOfferQueryBuilder $queryBuilder;

    private CompositeOfferRequestParser $requestParser;

    /**
     * @var OfferSearchServiceInterface&MockObject
     */
    private $searchService;

    private string $regionIndexName;

    private string $regionDocumentType;

    private MockQueryStringFactory $queryStringFactory;

    private NodeAwareFacetTreeNormalizer $facetTreeNormalizer;

    private OfferSearchController $controller;

    protected function setUp(): void
    {
        $this->queryBuilder = new MockOfferQueryBuilder();

        $this->requestParser = (new CompositeOfferRequestParser())
            ->withParser(new AgeRangeOfferRequestParser())
            ->withParser(new AvailabilityOfferRequestParser())
            ->withParser(new CalendarOfferRequestParser())
            ->withParser(new DistanceOfferRequestParser(
                new GeoDistanceParametersFactory(new MockDistanceFactory())
            ))
            ->withParser(new DocumentLanguageOfferRequestParser())
            ->withParser(new GroupByOfferRequestParser())
            ->withParser(new IsDuplicateOfferRequestParser())
            ->withParser(new SortByOfferRequestParser())
            ->withParser(new RelatedProductionRequestParser())
            ->withParser(new ContributorsRequestParser())
            ->withParser(new WorkflowStatusOfferRequestParser());

        $this->searchService = $this->createMock(OfferSearchServiceInterface::class);

        $this->regionIndexName = 'geoshapes';
        $this->regionDocumentType = 'region';

        $this->queryStringFactory = new MockQueryStringFactory();

        $this->facetTreeNormalizer = new NodeAwareFacetTreeNormalizer();

        $this->controller = new OfferSearchController(
            $this->queryBuilder,
            $this->requestParser,
            $this->searchService,
            $this->regionIndexName,
            $this->regionDocumentType,
            $this->queryStringFactory,
            $this->facetTreeNormalizer,
            new Consumer('id', '')
        );
    }

    /**
     * @test
     */
    public function it_returns_a_paged_collection_of_search_results_based_on_request_query_parameters(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 30,
                'limit' => 10,
                'q' => 'dag van de fiets AND labels:foo',
                'text' => '(foo OR bar) AND baz',
                'id' => '42926044-09f4-4bd5-bc35-427b2fc1a525',
                'locationId' => '652ab95e-fdff-41ce-8894-1b29dce0d230',
                'organizerId' => '392168d7-57c9-4488-8e2e-d492c843054b',
                'availableFrom' => '2017-04-26T00:00:00+01:00',
                'availableTo' => '2017-04-28T15:30:23+01:00',
                'workflowStatus' => 'DRAFT',
                'regions' => ['gem-leuven', 'prv-limburg'],
                'coordinates' => '-40,70',
                'distance' => '30km',
                'postalCode' => 3000,
                'addressCountry' => 'BE',
                'minAge' => 3,
                'maxAge' => 7,
                'allAges' => true,
                'price' => 1.55,
                'minPrice' => 0.99,
                'maxPrice' => 1.99,
                'audienceType' => 'members',
                'hasMediaObjects' => 'true',
                'hasVideos' => 'true',
                'labels' => ['foo', 'bar'],
                'locationLabels' => ['lorem'],
                'organizerLabels' => ['ipsum'],
                'textLanguages' => ['nl', 'en'],
                'languages' => ['nl', 'en', 'fr'],
                'completedLanguages' => ['nl', 'fr'],
                'calendarType' => 'single',
                'dateFrom' => '2017-05-01T00:00:00+01:00',
                'dateTo' => '2017-05-01T23:59:59+01:00',
                'localTimeFrom' => '0800',
                'localTimeTo' => '1600',
                'status' => 'Unavailable,TemporarilyUnavailable',
                'createdFrom' => '2017-05-01T13:33:37+01:00',
                'createdTo' => '2017-05-01T13:33:37+01:00',
                'modifiedFrom' => '2017-05-01T13:33:37+01:00',
                'modifiedTo' => '2017-05-01T13:33:37+01:00',
                'termIds' => ['1.45.678.95', 'azYBznHY'],
                'termLabels' => ['Jeugdhuis', 'Cultureel centrum'],
                'locationTermIds' => ['1234', '5678'],
                'uitpas' => 'true',
                'locationTermLabels' => ['foo1', 'bar1'],
                'organizerTermIds' => ['9012', '3456'],
                'organizerTermLabels' => ['foo2', 'bar2'],
                'facets' => ['regions'],
                'creator' => 'Jane Doe',
                'isDuplicate' => false,
                'productionId' => '5df0d426-84b3-4d2b-a7fc-e51270d84643',
                'recommendationFor' => 'be4f35c4-a093-4c85-8c9b-0afc16336381',
                'sort' => [
                    'distance' => 'asc',
                    'availableTo' => 'asc',
                    'score' => 'desc',
                    'completeness' => 'asc',
                    'created' => 'asc',
                    'modified' => 'desc',
                    'popularity' => 'desc',
                    'recommendationScore' => 'desc',
                ],
                'groupBy' => 'productionId',
            ]
        );

        /* @var OfferQueryBuilderInterface $expectedQueryBuilder */
        $expectedQueryBuilder = $this->queryBuilder;
        $expectedQueryBuilder = $expectedQueryBuilder
            ->withAdvancedQuery(
                new MockQueryString('dag van de fiets AND labels:foo'),
                new Language('nl'),
                new Language('en')
            )
            ->withTextQuery(
                '(foo OR bar) AND baz',
                new Language('nl'),
                new Language('en')
            )
            ->withAgeRangeFilter(new Age(3), new Age(7))
            ->withAllAgesFilter(true)
            ->withGeoDistanceFilter(
                new GeoDistanceParameters(
                    new Coordinates(
                        new Latitude(-40.0),
                        new Longitude(70.0)
                    ),
                    new MockDistance('30km')
                )
            )
            ->withLanguageFilter(new Language('nl'))
            ->withLanguageFilter(new Language('en'))
            ->withLanguageFilter(new Language('fr'))
            ->withCompletedLanguageFilter(new Language('nl'))
            ->withCompletedLanguageFilter(new Language('fr'))
            ->withSortByDistance(
                new Coordinates(
                    new Latitude(-40.0),
                    new Longitude(70.0)
                ),
                SortOrder::asc()
            )
            ->withSortByAvailableTo(SortOrder::asc())
            ->withSortByScore(SortOrder::desc())
            ->withSortByCompleteness(SortOrder::asc())
            ->withSortByCreated(SortOrder::asc())
            ->withSortByModified(SortOrder::desc())
            ->withSortByPopularity(SortOrder::desc())
            ->withSortByRecommendationScore('be4f35c4-a093-4c85-8c9b-0afc16336381', SortOrder::desc())
            ->withCdbIdFilter(
                new Cdbid('42926044-09f4-4bd5-bc35-427b2fc1a525')
            )
            ->withLocationCdbIdFilter(
                new Cdbid('652ab95e-fdff-41ce-8894-1b29dce0d230')
            )
            ->withOrganizerCdbidFilter(
                new Cdbid('392168d7-57c9-4488-8e2e-d492c843054b')
            )
            ->withWorkflowStatusFilter(
                new WorkflowStatus('DRAFT')
            )
            ->withAvailableRangeFilter(
                DateTimeFactory::fromAtom('2017-04-26T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2017-04-28T15:30:23+01:00')
            )
            ->withRegionFilter(
                $this->regionIndexName,
                $this->regionDocumentType,
                new RegionId('gem-leuven')
            )
            ->withRegionFilter(
                $this->regionIndexName,
                $this->regionDocumentType,
                new RegionId('prv-limburg')
            )
            ->withPostalCodeFilter(new PostalCode('3000'))
            ->withAddressCountryFilter(new Country('BE'))
            ->withAudienceTypeFilter(new AudienceType('members'))
            ->withPriceRangeFilter(Price::fromFloat(1.55), Price::fromFloat(1.55))
            ->withMediaObjectsFilter(true)
            ->withVideosFilter(true)
            ->withUiTPASFilter(true)
            ->withCreatorFilter(new Creator('Jane Doe'))
            ->withCreatedRangeFilter(
                DateTimeFactory::fromAtom('2017-05-01T13:33:37+01:00'),
                DateTimeFactory::fromAtom('2017-05-01T13:33:37+01:00')
            )
            ->withModifiedRangeFilter(
                DateTimeFactory::fromAtom('2017-05-01T13:33:37+01:00'),
                DateTimeFactory::fromAtom('2017-05-01T13:33:37+01:00')
            )
            ->withCalendarTypeFilter(new CalendarType('single'))
            ->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withDateFrom(DateTimeFactory::fromAtom('2017-05-01T00:00:00+01:00'))
                    ->withDateTo(DateTimeFactory::fromAtom('2017-05-01T23:59:59+01:00'))
                    ->withLocalTimeFrom(800)
                    ->withLocalTimeTo(1600)
                    ->withStatuses([Status::unavailable(), Status::temporarilyUnavailable()])
            )
            ->withTermIdFilter(new TermId('1.45.678.95'))
            ->withTermIdFilter(new TermId('azYBznHY'))
            ->withTermLabelFilter(new TermLabel('Jeugdhuis'))
            ->withTermLabelFilter(new TermLabel('Cultureel centrum'))
            ->withLocationTermIdFilter(new TermId('1234'))
            ->withLocationTermIdFilter(new TermId('5678'))
            ->withLocationTermLabelFilter(new TermLabel('foo1'))
            ->withLocationTermLabelFilter(new TermLabel('bar1'))
            ->withLabelFilter(new LabelName('foo'))
            ->withLabelFilter(new LabelName('bar'))
            ->withLocationLabelFilter(new LabelName('lorem'))
            ->withOrganizerLabelFilter(new LabelName('ipsum'))
            ->withDuplicateFilter(false)
            ->withProductionIdFilter('5df0d426-84b3-4d2b-a7fc-e51270d84643')
            ->withRecommendationForFilter('be4f35c4-a093-4c85-8c9b-0afc16336381')
            ->withFacet(FacetName::regions())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withGroupByProductionId();

        $expectedResultSet = new PagedResultSet(
            32,
            10,
            [
                new JsonDocument('3f2ba18c-59a9-4f65-a242-462ad467c72b', '{"@id": "events/1","@type":"Event"}'),
                new JsonDocument('39d06346-b762-4ccd-8b3a-142a8f6abbbe', '{"@id": "places/2","@type":"Place"}'),
            ]
        );

        $expectedResultSet = $expectedResultSet->withFacets(
            new FacetFilter(
                'regions',
                [
                    new FacetNode(
                        'gem-leuven',
                        new MultilingualString(new Language('nl'), 'Leuven'),
                        7,
                        [
                            new FacetNode(
                                'gem-wijgmaal',
                                new MultilingualString(new Language('nl'), 'Wijgmaal'),
                                3
                            ),
                        ]
                    ),
                ]
            )
        );

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $expectedJsonResponse = Json::encode(
            [
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 10,
                'totalItems' => 32,
                'member' => [
                    [
                        '@id' => 'events/1',
                        '@type' => 'Event',
                    ],
                    [
                        '@id' => 'places/2',
                        '@type' => 'Place',
                    ],
                ],
                'facet' => [
                    'regions' => [
                        'gem-leuven' => [
                            'name' => [
                                'nl' => 'Leuven',
                            ],
                            'count' => 7,
                            'children' => [
                                'gem-wijgmaal' => [
                                    'name' => [
                                        'nl' => 'Wijgmaal',
                                    ],
                                    'count' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $actualJsonResponse = $this->controller->__invoke(new ApiRequest($request))
            ->getBody();
        $this->assertEquals($expectedJsonResponse, $actualJsonResponse);
    }

    /**
     * @test
     */
    public function it_uses_the_default_limit_of_30_if_a_limit_of_0_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 0,
                'limit' => 0,
                'disableDefaultFilters' => true,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(30));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_negative_start_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => -1,
                'limit' => 30,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_start_over_10_000_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 10001,
                'limit' => 30,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_negative_limit_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 0,
                'limit' => -1,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_limit_over_2000_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 0,
                'limit' => 2001,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @dataProvider defaultsEnabledQueryParametersProvider
     *
     */
    public function it_uses_default_parameters_when_default_filters_are_not_disabled(array $queryParameters): void
    {
        $request = $this->getSearchRequestWithQueryParameters($queryParameters);

        $expectedQueryBuilder = $this->queryBuilder
            ->withWorkflowStatusFilter(new WorkflowStatus('APPROVED'), new WorkflowStatus('READY_FOR_VALIDATION'))
            ->withAvailableRangeFilter(
                DateTimeFactory::fromAtom('2017-04-26T08:34:21+00:00'),
                DateTimeFactory::fromAtom('2017-04-26T08:34:21+00:00')
            )
            ->withAddressCountryFilter(new Country('BE'))
            ->withAudienceTypeFilter(new AudienceType('everyone'))
            ->withDuplicateFilter(false);

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }


    public function defaultsEnabledQueryParametersProvider(): array
    {
        return [
            [[]],
            [['disableDefaultFilters' => 'false']],
            [['disableDefaultFilters' => '']],
            [['disableDefaultFilters' => null]],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_apply_default_filters_that_have_been_disabled_one_by_one(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'availableFrom' => '*',
                'availableTo' => '*',
                'addressCountry' => '*',
                'workflowStatus' => '*',
                'audienceType' => '*',
                'isDuplicate' => '*',
            ]
        );

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($this->queryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_expects_a_valid_available_from_and_available_to_date(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 0,
                'limit' => 0,
                'availableFrom' => '2017-04-01',
                'availableTo' => '2017-04-01',
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'availableFrom should be an ISO-8601 datetime, for example 2017-04-26T12:20:05+01:00'
        );

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_can_convert_spaces_in_date_parameters_to_plus_signs(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 30,
                'limit' => 10,
                'disableDefaultFilters' => true,
                'availableFrom' => '2017-04-01T00:00:00 01:00',
                'availableTo' => '2017-04-01T23:59:59 01:00',
                'dateFrom' => '2017-04-01T00:00:00 01:00',
                'dateTo' => '2017-04-01T23:59:59 01:00',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAvailableRangeFilter(
                DateTimeFactory::fromAtom('2017-04-01T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2017-04-01T23:59:59+01:00')
            )
            ->withDateRangeFilter(
                DateTimeFactory::fromAtom('2017-04-01T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2017-04-01T23:59:59+01:00')
            );

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_coordinates_is_given_without_distance(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            ['coordinates' => '-40,70']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required "distance" parameter missing when searching by coordinates.');

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_distance_is_given_without_coordinates(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            ['distance' => '30km']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required "coordinates" parameter missing when searching by distance.');

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_works_with_a_min_age_of_zero_and_or_a_max_age_of_zero(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 0,
                'limit' => 0,
                'disableDefaultFilters' => true,
                'minAge' => 0,
                'maxAge' => 0,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(30))
            ->withAgeRangeFilter(new Age(0), new Age(0));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_works_with_a_min_price_and_max_price_if_no_exact_price_is_set(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 0,
                'limit' => 0,
                'disableDefaultFilters' => true,
                'minPrice' => 0.14,
                'maxPrice' => 2.24,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(30))
            ->withPriceRangeFilter(Price::fromFloat(0.14), Price::fromFloat(2.24));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @param bool|string|int $stringValue
     * @dataProvider booleanStringDataProvider
     */
    public function it_converts_the_media_objects_toggle_parameter_to_a_correct_boolean(
        $stringValue,
        ?bool $booleanValue
    ): void {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'hasMediaObjects' => $stringValue,
                'disableDefaultFilters' => true,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder;

        if (!is_null($booleanValue)) {
            $expectedQueryBuilder = $expectedQueryBuilder
                ->withMediaObjectsFilter($booleanValue);
        }

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @param bool|string|int $stringValue
     * @dataProvider booleanStringDataProvider
     */
    public function it_converts_the_uitpas_toggle_parameter_to_a_correct_boolean(
        $stringValue,
        ?bool $booleanValue
    ): void {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'uitpas' => $stringValue,
                'disableDefaultFilters' => true,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder;

        if (!is_null($booleanValue)) {
            $expectedQueryBuilder = $expectedQueryBuilder
                ->withUiTPASFilter($booleanValue);
        }

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    public function booleanStringDataProvider(): array
    {
        return [
            [
                false,
                false,
            ],
            [
                true,
                true,
            ],
            [
                'false',
                false,
            ],
            [
                'FALSE',
                false,
            ],
            [
                '0',
                false,
            ],
            [
                0,
                false,
            ],
            [
                'true',
                true,
            ],
            [
                'TRUE',
                true,
            ],
            [
                '1',
                true,
            ],
            [
                1,
                true,
            ],
            [
                '',
                null,
            ],
            [
                null,
                null,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_can_handle_a_single_string_value_for_parameters_that_are_normally_arrays(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 30,
                'limit' => 10,
                'disableDefaultFilters' => true,
                'labels' => 'foo',
                'organizerLabels' => 'bar',
                'locationLabels' => 'baz',
                'text' => 'foobar',
                'textLanguages' => 'nl',
                'languages' => 'nl',
                'completedLanguages' => 'nl',
                'termIds' => '0.145.567.6',
                'termLabels' => 'Jeugdhuis',
                'facets' => 'regions',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withTextQuery('foobar', new Language('nl'))
            ->withLanguageFilter(new Language('nl'))
            ->withCompletedLanguageFilter(new Language('nl'))
            ->withTermIdFilter(new TermId('0.145.567.6'))
            ->withTermLabelFilter(new TermLabel('Jeugdhuis'))
            ->withLabelFilter(new LabelName('foo'))
            ->withLocationLabelFilter(new LabelName('baz'))
            ->withOrganizerLabelFilter(new LabelName('bar'))
            ->withFacet(FacetName::regions());

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_should_split_multiple_calendar_types_delimited_with_a_comma(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 30,
                'limit' => 10,
                'disableDefaultFilters' => true,
                'calendarType' => 'SINGLE,MULTIPLE',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withCalendarTypeFilter(new CalendarType('SINGLE'), new CalendarType('MULTIPLE'));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_an_unknown_facet_name_is_given(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown facet name 'bla'.");

        $request = $this->getSearchRequestWithQueryParameters(
            ['facets' => ['regions', 'bla']]
        );

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_an_unknown_address_country_is_given(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown country code 'foobar'.");

        $request = $this->getSearchRequestWithQueryParameters(
            ['addressCountry' => 'foobar']
        );

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_transforms_the_request_address_country_to_uppercase(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'disableDefaultFilters' => 'true',
                'addressCountry' => 'nl',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withAddressCountryFilter(new Country('NL'));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @dataProvider malformedDateTimeProvider
     * @param bool|string|int $malformedDateTimeAsString
     * @todo PHP8 upgrade: change Type hint to string|bool
     */
    public function it_throws_an_exception_for_a_malformed_date_from($malformedDateTimeAsString): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            ['dateFrom' => $malformedDateTimeAsString]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('dateFrom should be an ISO-8601 datetime, for example 2017-04-26T12:20:05+01:00');
        $this->controller->__invoke(new ApiRequest($request));
    }


    public function malformedDateTimeProvider(): array
    {
        return [
            ['2017'],
            ['2017-01'],
            ['2017-01-01'],
            ['2017-01-01T'],
            ['2017-01-01T23'],
            ['2017-01-01T23:59'],
            ['2017-01-01T23:59:59'],
            [false],
            [true],
            [0],
            [1],
            ['now'],
            ['1493726880'],
        ];
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_sort_is_not_an_array(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'sort' => 'availableTo asc',
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sorting syntax given.');

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_sort_field_is_invalid(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'sort' => [
                    'availableTo' => 'asc',
                    'name.nl' => 'asc',
                    'score' => 'desc',
                ],
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sort field 'name.nl' given.");

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_sort_order_is_invalid(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'sort' => [
                    'availableTo' => 'ascending',
                    'score' => 'descending',
                ],
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sort order 'ascending' given.");

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_sort_order_distance_and_missing_coordinates(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'sort' => [
                    'distance' => 'asc',
                ],
            ]
        );

        $this->expectException(MissingParameter::class);
        $this->expectExceptionMessage('Required "coordinates" parameter missing when sorting by distance.');

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_sort_order_recommendation_score_and_missing_recommendation_for(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'sort' => [
                    'recommendationScore' => 'asc',
                ],
            ]
        );

        $this->expectException(MissingParameter::class);
        $this->expectExceptionMessage('Required "recommendationFor" parameter missing when sorting by recommendation score.');

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_an_unknown_status(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(['status' => 'Available,Foo']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown status value "Foo"');

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_uses_a_status_filter_only_if_no_date_from_or_date_to_or_time_from_or_time_to_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'disableDefaultFilters' => true,
                'status' => 'Available',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStatusFilter(Status::available());

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_uses_a_date_range_filter_only_if_no_status_or_time_from_or_time_to_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'disableDefaultFilters' => true,
                'dateFrom' => '2017-05-01T00:00:00+01:00',
                'dateTo' => '2017-05-01T23:59:59+01:00',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withDateRangeFilter(
                DateTimeFactory::fromAtom('2017-05-01T00:00:00+01:00'),
                DateTimeFactory::fromAtom('2017-05-01T23:59:59+01:00')
            );

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_uses_a_time_filter_only_if_no_date_from_or_date_to_or_status_is_given(): void
    {
        $request = $this->getSearchRequestWithQueryParameters(
            [
                'disableDefaultFilters' => true,
                'localTimeFrom' => '0800',
                'localTimeTo' => '1600',
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withLocalTimeRangeFilter(800, 1600);

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @dataProvider unknownParameterProvider
     */
    public function it_rejects_queries_with_unknown_parameters(
        ApiRequestInterface $request,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->controller->__invoke(new ApiRequest($request));
    }

    public function unknownParameterProvider(): array
    {
        return [
            'single unknown parameter' => [
                'request' => $this->getSearchRequestWithQueryParameters(
                    [
                        'fat' => 'lip',
                    ]
                ),
                'expectedExceptionMessage' => 'Unknown query parameter(s): fat',
            ],
            'multiple unknown parameter' => [
                'request' => $this->getSearchRequestWithQueryParameters(
                    [
                        'fat' => 'lip',
                        'bat' => 'cave',
                    ]
                ),
                'expectedExceptionMessage' => 'Unknown query parameter(s): fat, bat',
            ],
            'unknown and whitelisted parameter' => [
                'request' => $this->getSearchRequestWithQueryParameters(
                    [
                        'id' => '5333ED41-91FA-43F4-82BA-F28A9AC96A6E',
                        'bat' => 'cave',
                    ]
                ),
                'expectedExceptionMessage' => 'Unknown query parameter(s): bat',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_add_the_default_query_of_the_api_consumer_if_they_have_one(): void
    {
        $controller = new OfferSearchController(
            $this->queryBuilder,
            $this->requestParser,
            $this->searchService,
            $this->regionIndexName,
            $this->regionDocumentType,
            $this->queryStringFactory,
            $this->facetTreeNormalizer,
            new Consumer('d568d2e9-3b53-4704-82a1-eaccf91a6337', 'labels:foo')
        );

        $request = $this->getSearchRequestWithQueryParameters(
            [
                'start' => 10,
                'limit' => 30,
                'disableDefaultFilters' => true,
                'apiKey' => 'd568d2e9-3b53-4704-82a1-eaccf91a6337',
                'q' => 'labels:bar',
            ]
        );

        /* @var OfferQueryBuilderInterface $expectedQueryBuilder */
        $expectedQueryBuilder = $this->queryBuilder;
        $expectedQueryBuilder = $expectedQueryBuilder
            ->withAdvancedQuery(
                new MockQueryString('labels:foo')
            )
            ->withAdvancedQuery(
                new MockQueryString('labels:bar')
            )
            ->withStartAndLimit(new Start(10), new Limit(30));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $controller->__invoke(new ApiRequest($request));
    }

    private function getSearchRequestWithQueryParameters(array $queryParameters): ServerRequestInterface
    {
        $_SERVER['REQUEST_TIME'] = 1493195661;
        $request = ServerRequestFactory::createFromGlobals()
            ->withRequestTarget('http://search.uitdatabank.be/offers/')
            ->withQueryParams($queryParameters)
            ->withMethod('GET');
        return new ApiRequest($request);
    }


    private function expectQueryBuilderWillReturnResultSet(
        OfferQueryBuilderInterface $expectedQueryBuilder,
        PagedResultSet $pagedResultSet
    ): void {
        $this->searchService->expects($this->once())
            ->method('search')
            ->with(
                $this->callback(
                    function (OfferQueryBuilderInterface $actualQueryBuilder) use ($expectedQueryBuilder): bool {
                        $this->assertEquals(
                            Json::encodeWithOptions($expectedQueryBuilder->build(), JSON_PRETTY_PRINT),
                            Json::encodeWithOptions($actualQueryBuilder->build(), JSON_PRETTY_PRINT)
                        );
                        return true;
                    }
                )
            )
            ->willReturn($pagedResultSet);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

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
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\DistanceOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Parameters\GeoDistanceParametersFactory;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Request;

final class OrganizerSearchControllerTest extends TestCase
{
    private MockOrganizerQueryBuilder $queryBuilder;

    /**
     * @var OrganizerSearchServiceInterface|MockObject
     */
    private $searchService;

    private string $regionIndexName;

    private string $regionDocumentType;

    private OrganizerSearchController $controller;

    protected function setUp(): void
    {
        $this->queryBuilder = new MockOrganizerQueryBuilder();
        $this->searchService = $this->createMock(OrganizerSearchServiceInterface::class);

        $this->regionIndexName = 'geoshapes';
        $this->regionDocumentType = 'region';

        $this->controller = new OrganizerSearchController(
            $this->queryBuilder,
            $this->searchService,
            $this->regionIndexName,
            $this->regionDocumentType,
            (new CompositeOrganizerRequestParser())
                ->withParser(new DistanceOrganizerRequestParser(
                    new GeoDistanceParametersFactory(new MockDistanceFactory())
                ))
                ->withParser(new WorkflowStatusOrganizerRequestParser())
                ->withParser(new SortByOrganizerRequestParser()),
            new MockQueryStringFactory(),
            new NodeAwareFacetTreeNormalizer(),
            new Consumer(null, null)
        );
    }

    /**
     * @test
     */
    public function it_returns_a_paged_collection_of_search_results_based_on_request_query_parameters(): void
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => 30,
                'limit' => 10,
                'q' => 'Foo bar',
                'textLanguages' => ['nl', 'en'],
                'name' => 'Foo',
                'website' => 'http://foo.bar',
                'postalCode' => 3000,
                'addressCountry' => 'NL',
                'regions' => ['gem-leuven', 'prv-limburg'],
                'coordinates' => '-40,70',
                'distance' => '30km',
                'facets' => ['regions'],
                'creator' => 'Jan Janssens',
                'hasImages' => 'true',
                'labels' => [
                    'Uitpas',
                    'foo',
                ],
                'workflowStatus' => 'ACTIVE,DELETED',
                'domain' => 'www.publiq.be',
                'sort' => [
                    'score' => 'desc',
                    'created' => 'asc',
                    'modified' => 'desc',
                ],
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withAutoCompleteFilter('Foo')
            ->withAdvancedQuery(
                new MockQueryString('Foo bar'),
                new Language('nl'),
                new Language('en')
            )
            ->withWebsiteFilter('http://foo.bar')
            ->withDomainFilter('www.publiq.be')
            ->withPostalCodeFilter(new PostalCode('3000'))
            ->withAddressCountryFilter(new Country('NL'))
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
            ->withGeoDistanceFilter(
                new GeoDistanceParameters(
                    new Coordinates(
                        new Latitude(-40.0),
                        new Longitude(70.0)
                    ),
                    new MockDistance('30km')
                )
            )
            ->withCreatorFilter(new Creator('Jan Janssens'))
            ->withSortByScore(SortOrder::desc())
            ->withSortByCreated(SortOrder::asc())
            ->withSortByModified(SortOrder::desc())
            ->withImagesFilter(true)
            ->withLabelFilter(new LabelName('Uitpas'))
            ->withLabelFilter(new LabelName('foo'))
            ->withWorkflowStatusFilter(new WorkflowStatus('ACTIVE'), new WorkflowStatus('DELETED'))
            ->withFacet(FacetName::regions())
            ->withStartAndLimit(new Start(30), new Limit(10));

        $expectedResultSet = new PagedResultSet(
            32,
            10,
            [
                new JsonDocument('3f2ba18c-59a9-4f65-a242-462ad467c72b', '{"@id":"1","@type":"Organizer"}'),
                new JsonDocument('39d06346-b762-4ccd-8b3a-142a8f6abbbe', '{"@id":"2","@type":"Organizer"}'),
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
                    ['@id' => '1', '@type' => 'Organizer'],
                    ['@id' => '2', '@type' => 'Organizer'],
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

        $actualJsonResponse = $this->controller
            ->__invoke(new ApiRequest($request))
            ->getBody()
            ->__toString();

        $this->assertEquals($expectedJsonResponse, $actualJsonResponse);
    }

    /**
     * @test
     */
    public function it_uses_the_default_limit_of_30_if_a_limit_of_0_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => 0,
                'limit' => 0,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(30))
            ->withWorkflowStatusFilter(new WorkflowStatus('ACTIVE'));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_negative_start_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => -1,
                'limit' => 30,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_start_over_10_000_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => 10001,
                'limit' => 30,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_negative_limit_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => 0,
                'limit' => -1,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_throws_if_a_limit_over_2000_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => 0,
                'limit' => 2001,
                'disableDefaultFilters' => true,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     */
    public function it_filters_out_deleted_organizers_by_default(): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        $expectedQueryBuilder = $this->queryBuilder
            ->withStartAndLimit(new Start(0), new Limit(30))
            ->withWorkflowStatusFilter(new WorkflowStatus('ACTIVE'));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @dataProvider unknownParameterProvider
     */
    public function it_rejects_queries_with_unknown_parameters(
        Request $request,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->controller->__invoke(new ApiRequest($request));
    }

    public function unknownParameterProvider(): array
    {
        $uri = (new UriFactory())->createUri('http://search.uitdatabank.be/organizers/');
        $request = ServerRequestFactory::createFromGlobals()->withUri($uri);
        return [
            'single unknown parameter' => [
                'request' => $request->withQueryParams(
                    [
                        'frog' => [
                            'face',
                        ],
                    ]
                ),
                'expectedExceptionMessage' => 'Unknown query parameter(s): frog',
            ],
            'multiple unknown parameter' => [
                'request' => $request->withQueryParams(
                    [
                        'frog' => [
                            'face',
                        ],
                        'bat' => [
                            'cave',
                        ],
                    ]
                ),
                'expectedExceptionMessage' => 'Unknown query parameter(s): frog, bat',
            ],
            'unknown and whitelisted parameter' => [
                'request' => $request->withQueryParams(
                    [
                        'website' => [
                            'https://du.de',
                        ],
                        'bat' => [
                            'cave',
                        ],
                    ]
                ),
                'expectedExceptionMessage' => 'Unknown query parameter(s): bat',
            ],
        ];
    }


    private function expectQueryBuilderWillReturnResultSet(
        OrganizerQueryBuilderInterface $expectedQueryBuilder,
        PagedResultSet $pagedResultSet
    ): void {
        $this->searchService->expects($this->once())
            ->method('search')
            ->with(
                $this->callback(
                    function (OrganizerQueryBuilderInterface $actualQueryBuilder) use ($expectedQueryBuilder) {
                        $this->assertEquals(
                            $expectedQueryBuilder->build(),
                            $actualQueryBuilder->build()
                        );
                        return true;
                    }
                )
            )
            ->willReturn($pagedResultSet);
    }
}

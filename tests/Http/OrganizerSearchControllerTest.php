<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\Search\Address\PostalCode;
use CultuurNet\UDB3\Search\Creator;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Request;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;
use ValueObjects\Web\Domain;
use ValueObjects\Web\Url;

final class OrganizerSearchControllerTest extends TestCase
{
    /**
     * @var ApiKeyReaderInterface|MockObject
     */
    private $apiKeyReader;

    /**
     * @var MockOrganizerQueryBuilder
     */
    private $queryBuilder;

    /**
     * @var OrganizerSearchServiceInterface|MockObject
     */
    private $searchService;

    /**
     * @var OrganizerSearchController
     */
    private $controller;

    /**
     * @var \CultuurNet\UDB3\Search\QueryStringFactory
     */
    private $queryStringFactory;

    protected function setUp()
    {
        $this->apiKeyReader = $this->createMock(ApiKeyReaderInterface::class);
        $this->queryBuilder = new MockOrganizerQueryBuilder();
        $this->searchService = $this->createMock(OrganizerSearchServiceInterface::class);
        $this->queryStringFactory = new MockQueryStringFactory();
        $this->controller = new OrganizerSearchController(
            $this->apiKeyReader,
            $this->queryBuilder,
            $this->searchService,
            (new CompositeOrganizerRequestParser())
                ->withParser(new WorkflowStatusOrganizerRequestParser())
                ->withParser(new SortByOrganizerRequestParser()),
            $this->queryStringFactory
        );
    }

    /**
     * @test
     */
    public function it_returns_a_paged_collection_of_search_results_based_on_request_query_parameters()
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
                'creator' => 'Jan Janssens',
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
            ->withWebsiteFilter(Url::fromNative('http://foo.bar'))
            ->withDomainFilter(Domain::specifyType('www.publiq.be'))
            ->withPostalCodeFilter(new PostalCode('3000'))
            ->withAddressCountryFilter(new Country(CountryCode::fromNative('NL')))
            ->withCreatorFilter(new Creator('Jan Janssens'))
            ->withSortByScore(SortOrder::DESC())
            ->withSortByCreated(SortOrder::ASC())
            ->withSortByModified(SortOrder::DESC())
            ->withLabelFilter(new LabelName('Uitpas'))
            ->withLabelFilter(new LabelName('foo'))
            ->withWorkflowStatusFilter(new WorkflowStatus('ACTIVE'), new WorkflowStatus('DELETED'))
            ->withStart(new Start(30))
            ->withLimit(new Limit(10));

        $expectedResultSet = new PagedResultSet(
            32,
            10,
            [
                new JsonDocument('3f2ba18c-59a9-4f65-a242-462ad467c72b', '{"@id":"1","@type":"Organizer"}'),
                new JsonDocument('39d06346-b762-4ccd-8b3a-142a8f6abbbe', '{"@id":"2","@type":"Organizer"}'),
            ]
        );

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $expectedJsonResponse = json_encode(
            [
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 10,
                'totalItems' => 32,
                'member' => [
                    ['@id' => '1', '@type' => 'Organizer'],
                    ['@id' => '2', '@type' => 'Organizer'],
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
    public function it_uses_the_default_limit_of_30_if_a_limit_of_0_is_given()
    {
        $request = ServerRequestFactory::createFromGlobals()->withQueryParams(
            [
                'start' => 0,
                'limit' => 0,
            ]
        );

        $expectedQueryBuilder = $this->queryBuilder
            ->withStart(new Start(0))
            ->withLimit(new Limit(30))
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
    public function it_filters_out_deleted_organizers_by_default()
    {
        $request = ServerRequestFactory::createFromGlobals();

        $expectedQueryBuilder = $this->queryBuilder
            ->withStart(new Start(0))
            ->withLimit(new Limit(30))
            ->withWorkflowStatusFilter(new WorkflowStatus('ACTIVE'));

        $expectedResultSet = new PagedResultSet(30, 0, []);

        $this->expectQueryBuilderWillReturnResultSet($expectedQueryBuilder, $expectedResultSet);

        $this->controller->__invoke(new ApiRequest($request));
    }

    /**
     * @test
     * @dataProvider unknownParameterProvider
     *
     * @param string $expectedExceptionMessage
     */
    public function it_rejects_queries_with_unknown_parameters(
        Request $request,
        $expectedExceptionMessage
    ) {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->controller->__invoke(new ApiRequest($request));
    }

    public function unknownParameterProvider()
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
    ) {
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

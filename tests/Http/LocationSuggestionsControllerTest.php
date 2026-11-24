<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class LocationSuggestionsControllerTest extends TestCase
{
    /**
     * @var ElasticSearchClient&MockObject
     */
    private $client;

    private LocationSuggestionsController $controller;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ElasticSearchClient::class);
        $this->controller = new LocationSuggestionsController($this->client, 'location_suggestions_read');
    }

    /**
     * @test
     */
    public function it_returns_the_suggestions_for_the_search_text(): void
    {
        $this->client->expects($this->once())
            ->method('search')
            ->with([
                'index' => 'location_suggestions_read',
                'body' => [
                    'size' => 10,
                    'query' => [
                        'function_score' => [
                            'query' => [
                                'bool' => [
                                    'must' => [['match_bool_prefix' => ['search_text' => ['query' => 'lentedreef leuv', 'operator' => 'and']]]],
                                    'should' => [['match' => ['name' => ['query' => 'lentedreef leuv', 'boost' => 10]]]],
                                    'filter' => [['terms' => ['type' => ['street', 'municipality', 'submunicipality']]]],
                                ],
                            ],
                            'field_value_factor' => ['field' => 'address_count', 'modifier' => 'log1p', 'factor' => 0.5, 'missing' => 1],
                            'boost_mode' => 'multiply',
                        ],
                    ],
                ],
            ])
            ->willReturn(['hits' => ['hits' => [
                ['_source' => [
                    'type' => 'street',
                    'label' => 'Lentedreef, 3010 Kessel-Lo',
                    'search_text' => 'Lentedreef 3010 Kessel-Lo Leuven',
                    'location' => ['lat' => 50.88943, 'lon' => 4.72002],
                ]],
            ]]]);

        $response = ($this->controller)($this->request('lentedreef leuv'));

        $this->assertSame(
            ['member' => [
                [
                    'type' => 'street',
                    'label' => 'Lentedreef, 3010 Kessel-Lo',
                    'searchFilter' => ['coordinates' => '50.88943,4.72002'],
                ],
            ]],
            Json::decodeAssociatively((string) $response->getBody())
        );
    }

    /**
     * @test
     * @dataProvider provideSearchTexts
     * @param list<string> $expectedTypes
     */
    public function it_matches_a_trailing_number_whole_and_only_suggests_addresses_when_a_number_is_typed(
        string $searchText,
        string $expectedMatchType,
        array $expectedTypes
    ): void {
        $this->client->expects($this->once())
            ->method('search')
            ->with($this->callback(
                fn (array $params): bool => $params['body']['query']['function_score']['query']['bool'] === [
                    'must' => [[$expectedMatchType => ['search_text' => ['query' => $searchText, 'operator' => 'and']]]],
                    'should' => [['match' => ['name' => ['query' => $searchText, 'boost' => 10]]]],
                    'filter' => [['terms' => ['type' => $expectedTypes]]],
                ]
            ))
            ->willReturn(['hits' => ['hits' => []]]);

        ($this->controller)($this->request($searchText));
    }

    public function provideSearchTexts(): array
    {
        return [
            'street being typed' => ['lentedreef', 'match_bool_prefix', ['street', 'municipality', 'submunicipality']],
            'house number at the end' => ['lentedreef 2', 'match', ['street', 'submunicipality', 'address']],
            'house number with a letter' => ['kerkstraat 12a', 'match', ['street', 'submunicipality', 'address']],
            'city after the house number' => ['kerkstraat 12 gen', 'match_bool_prefix', ['street', 'submunicipality', 'address']],
            'postcode' => ['3010', 'match_bool_prefix', ['submunicipality']],
            'postcode being typed' => ['30', 'match_bool_prefix', ['submunicipality']],
        ];
    }

    /**
     * @test
     */
    public function it_gives_a_municipality_its_region_as_search_filter(): void
    {
        $this->client->method('search')->willReturn(['hits' => ['hits' => [
            ['_source' => [
                'type' => 'municipality',
                'label' => 'Leuven',
                'regions' => ['nis-24062'],
                'location' => ['lat' => 50.87959, 'lon' => 4.70093],
            ]],
        ]]]);

        $response = ($this->controller)($this->request('leuven'));

        $this->assertSame(
            ['member' => [
                [
                    'type' => 'municipality',
                    'label' => 'Leuven',
                    'searchFilter' => ['regions' => ['nis-24062']],
                ],
            ]],
            Json::decodeAssociatively((string) $response->getBody())
        );
    }

    /**
     * @test
     */
    public function it_shows_a_sub_municipality_as_a_municipality_with_its_region_as_search_filter(): void
    {
        $this->client->method('search')->willReturn(['hits' => ['hits' => [
            ['_source' => [
                'type' => 'submunicipality',
                'label' => 'Kessel-Lo (3010)',
                'regions' => ['nis-24062C'],
                'location' => ['lat' => 50.88943, 'lon' => 4.72002],
            ]],
        ]]]);

        $response = ($this->controller)($this->request('3010'));

        $this->assertSame(
            ['member' => [
                [
                    'type' => 'municipality',
                    'label' => 'Kessel-Lo (3010)',
                    'searchFilter' => ['regions' => ['nis-24062C']],
                ],
            ]],
            Json::decodeAssociatively((string) $response->getBody())
        );
    }

    /**
     * @test
     */
    public function it_refuses_more_than_one_search_text(): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        ($this->controller)(new ApiRequest(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/location-suggestions')
                ->withQueryParams(['q' => ['lentedreef', 'kerkstraat']])
        ));
    }

    private function request(string $searchText): ApiRequest
    {
        return new ApiRequest(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/location-suggestions')
                ->withQueryParams(['q' => $searchText])
        );
    }
}

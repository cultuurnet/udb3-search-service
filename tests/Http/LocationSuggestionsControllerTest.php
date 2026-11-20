<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\MissingParameter;
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
    public function it_returns_the_suggestions_for_the_typed_text(): void
    {
        $this->client->expects($this->once())
            ->method('search')
            ->with([
                'index' => 'location_suggestions_read',
                'body' => [
                    'size' => 10,
                    'query' => [
                        'match_bool_prefix' => ['search_text' => ['query' => 'lentedreef leuv', 'operator' => 'and']],
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
                    'geo' => ['latitude' => 50.88943, 'longitude' => 4.72002],
                ],
            ]],
            Json::decodeAssociatively((string) $response->getBody())
        );
    }

    /**
     * @test
     */
    public function it_requires_typed_text(): void
    {
        $this->expectException(MissingParameter::class);

        ($this->controller)($this->request('  '));
    }

    /**
     * @test
     */
    public function it_refuses_more_than_one_typed_text(): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        ($this->controller)(new ApiRequest(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/location-suggestions')
                ->withQueryParams(['q' => ['lentedreef', 'kerkstraat']])
        ));
    }

    /**
     * @test
     */
    public function it_refuses_typed_text_longer_than_120_characters(): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        ($this->controller)($this->request(str_repeat('a', 121)));
    }

    private function request(string $typedText): ApiRequest
    {
        return new ApiRequest(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/location-suggestions')
                ->withQueryParams(['q' => $typedText])
        );
    }
}

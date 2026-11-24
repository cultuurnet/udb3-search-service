<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\CalendarSummary;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchRequestFailed;
use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class CalendarSummaryControllerTest extends TestCase
{
    private const OFFER_ID = 'd1f0e71d-a9a8-4069-81fb-530134502c58';

    /**
     * @var ElasticSearchClient&MockObject
     */
    private $client;

    private CalendarSummaryController $controller;

    private string $defaultTimezone;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ElasticSearchClient::class);
        $this->controller = new CalendarSummaryController($this->client, 'udb3_core_event_read', 'udb3_core_place_read');
        $this->defaultTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        // The formatters set the process-wide default timezone, which would leak into later tests.
        date_default_timezone_set($this->defaultTimezone);
    }

    /**
     * @test
     */
    public function it_returns_a_large_dutch_text_summary_of_an_event_by_default(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with([
                'index' => 'udb3_core_event_read',
                'id' => self::OFFER_ID,
                '_source' => ['@type', 'originalEncodedJsonLd'],
            ])
            ->willReturn($this->document('event'));

        $response = ($this->controller)($this->request([]), ['offerType' => 'events', 'offerId' => self::OFFER_ID]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('Zaterdag 25 mei 2030 van 20:00 tot 22:00', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function it_returns_an_html_summary_of_a_place_in_the_requested_language_and_size(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with($this->callback(fn (array $params): bool => $params['index'] === 'udb3_core_place_read'))
            ->willReturn($this->document('place'));

        $response = ($this->controller)(
            $this->request(['language' => 'fr', 'size' => 'xs'], 'text/html'),
            ['offerType' => 'places', 'offerId' => self::OFFER_ID]
        );

        $this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            '<span class="cf-weekday cf-meta">Sam.</span> <span class="cf-date">25</span> <span class="cf-month">mai</span> <span class="cf-year">2030</span>',
            (string) $response->getBody()
        );
    }

    /**
     * @test
     */
    public function it_renders_the_hours_in_the_requested_timezone(): void
    {
        $this->client->method('get')->willReturn($this->document('event'));

        $response = ($this->controller)(
            $this->request(['timezone' => 'America/New_York']),
            ['offerType' => 'events', 'offerId' => self::OFFER_ID]
        );

        $this->assertSame('Zaterdag 25 mei 2030 van 14:00 tot 16:00', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function it_throws_not_found_for_an_unknown_offer(): void
    {
        $this->client->method('get')
            ->willThrowException(new ElasticSearchRequestFailed('Not Found', 404, []));

        $this->expectException(OfferNotFound::class);
        $this->expectExceptionMessage('No event found with id ' . self::OFFER_ID . '.');

        ($this->controller)($this->request([]), ['offerType' => 'events', 'offerId' => self::OFFER_ID]);
    }

    /**
     * @test
     * @dataProvider provideOtherTypes
     */
    public function it_throws_not_found_when_the_id_belongs_to_another_type(string $requestedType, ?string $foundType): void
    {
        $document = $this->document($foundType ?? 'event');
        if ($foundType === null) {
            unset($document['_source']['@type']);
        }
        $this->client->method('get')->willReturn($document);

        $this->expectException(OfferNotFound::class);

        ($this->controller)($this->request([]), ['offerType' => $requestedType, 'offerId' => self::OFFER_ID]);
    }

    public function provideOtherTypes(): array
    {
        return [
            'place requested as event' => ['events', 'place'],
            'event requested as place' => ['places', 'event'],
            'organizer requested as event' => ['events', 'organizer'],
            'document without a type' => ['events', null],
        ];
    }

    /**
     * @test
     */
    public function it_lets_other_elasticsearch_failures_through(): void
    {
        $failure = new ElasticSearchRequestFailed('Service Unavailable', 503, []);
        $this->client->method('get')->willThrowException($failure);

        $this->expectExceptionObject($failure);

        ($this->controller)($this->request([]), ['offerType' => 'events', 'offerId' => self::OFFER_ID]);
    }

    private function document(string $type): array
    {
        $jsonLd = [
            '@id' => 'https://io.uitdatabank.dev/' . $type . '/' . self::OFFER_ID,
            '@context' => '/contexts/' . $type,
            'calendarType' => 'single',
            'startDate' => '2030-05-25T20:00:00+02:00',
            'endDate' => '2030-05-25T22:00:00+02:00',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'subEvent' => [
                [
                    'startDate' => '2030-05-25T20:00:00+02:00',
                    'endDate' => '2030-05-25T22:00:00+02:00',
                    'status' => ['type' => 'Available'],
                    'bookingAvailability' => ['type' => 'Available'],
                ],
            ],
        ];

        return [
            'found' => true,
            '_source' => [
                '@type' => ucfirst($type),
                'originalEncodedJsonLd' => Json::encode($jsonLd),
            ],
        ];
    }

    private function request(array $queryParameters, ?string $accept = null): ApiRequest
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/events/' . self::OFFER_ID . '/calendar-summary')
            ->withQueryParams($queryParameters);

        return new ApiRequest($accept === null ? $request : $request->withHeader('Accept', $accept));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchRequestFailed;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PlaceLocationSuggestionProjectorTest extends TestCase
{
    private const PLACE_ID = '23017cb7-e515-47b4-87c4-780735acc942';

    /**
     * @var JsonDocumentFetcher&MockObject
     */
    private $fetcher;

    /**
     * @var ElasticSearchClient&MockObject
     */
    private $client;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    private PlaceLocationSuggestionProjector $projector;

    protected function setUp(): void
    {
        $this->fetcher = $this->createMock(JsonDocumentFetcher::class);
        $this->client = $this->createMock(ElasticSearchClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->projector = new PlaceLocationSuggestionProjector(
            $this->fetcher,
            new PlaceDocuments(new MunicipalityRegions([])),
            $this->client,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_indexes_a_public_place_through_the_write_alias(): void
    {
        $this->fetchesPlace('APPROVED');

        $this->client->expects($this->once())
            ->method('index')
            ->with($this->callback(
                fn (array $params): bool => $params['index'] === 'location_suggestions_places_write'
                    && $params['id'] === 'place:' . self::PLACE_ID
                    && $params['body']['label'] === 'Het Depot (Martelarenplein 12, 3000 Leuven)'
                    && $params['require_alias'] === true
            ));
        $this->client->expects($this->never())->method('delete');

        $this->projector->handle(self::placeProjected());
    }

    /**
     * @test
     */
    public function it_removes_a_place_that_is_no_longer_suggested(): void
    {
        $this->fetchesPlace('DELETED');

        $this->client->expects($this->once())
            ->method('delete')
            ->with(['index' => 'location_suggestions_places_write', 'id' => 'place:' . self::PLACE_ID]);
        $this->client->expects($this->never())->method('index');

        $this->projector->handle(self::placeProjected());
    }

    /**
     * @test
     */
    public function it_keeps_the_suggestion_when_the_place_cannot_be_fetched(): void
    {
        $this->fetcher->method('fetch')->willReturn(null);

        $this->client->expects($this->never())->method('index');
        $this->client->expects($this->never())->method('delete');

        $this->projector->handle(self::placeProjected());
    }

    /**
     * @test
     */
    public function it_ignores_other_events(): void
    {
        $this->fetcher->expects($this->never())->method('fetch');

        $this->projector->handle(
            new DomainMessage(self::PLACE_ID, 0, new Metadata(), new \stdClass(), DateTime::now())
        );
    }

    /**
     * @test
     */
    public function it_ignores_removing_a_suggestion_that_is_already_gone(): void
    {
        $this->fetchesPlace('DELETED');
        $this->client->method('delete')->willThrowException(new ElasticSearchRequestFailed('not found', 404, []));

        $this->logger->expects($this->never())->method('warning');

        $this->projector->handle(self::placeProjected());
    }

    /**
     * @test
     */
    public function it_warns_when_there_is_no_places_index_yet(): void
    {
        $this->fetchesPlace('APPROVED');
        $this->client->method('index')->willThrowException(new ElasticSearchRequestFailed('no such index', 404, []));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('location-suggestions:index-places'));

        $this->projector->handle(self::placeProjected());
    }

    /**
     * @test
     */
    public function it_fails_the_message_on_any_other_error_so_it_is_retried(): void
    {
        $this->fetchesPlace('APPROVED');
        $this->client->method('index')->willThrowException(new ElasticSearchRequestFailed('unavailable', 503, []));

        $this->expectException(ElasticSearchRequestFailed::class);

        $this->projector->handle(self::placeProjected());
    }

    private function fetchesPlace(string $workflowStatus): void
    {
        $this->fetcher->method('fetch')
            ->with(self::PLACE_ID, 'place/' . self::PLACE_ID)
            ->willReturn(new JsonDocument(self::PLACE_ID, Json::encode([
                'workflowStatus' => $workflowStatus,
                'name' => ['nl' => 'Het Depot'],
                'address' => ['nl' => [
                    'streetAddress' => 'Martelarenplein 12',
                    'postalCode' => '3000',
                    'addressLocality' => 'Leuven',
                    'addressCountry' => 'BE',
                ]],
                'geo' => ['latitude' => 50.8814, 'longitude' => 4.7155],
            ])));
    }

    private static function placeProjected(): DomainMessage
    {
        return new DomainMessage(
            self::PLACE_ID,
            0,
            new Metadata(),
            new PlaceProjectedToJSONLD(self::PLACE_ID, 'place/' . self::PLACE_ID),
            DateTime::now()
        );
    }
}

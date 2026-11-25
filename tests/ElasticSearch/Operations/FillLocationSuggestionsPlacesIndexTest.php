<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\LocationSuggestions\MunicipalityRegions;
use CultuurNet\UDB3\Search\LocationSuggestions\PlaceDocuments;
use Psr\Log\LoggerInterface;

final class FillLocationSuggestionsPlacesIndexTest extends AbstractOperationTestCase
{
    protected function createOperation(ElasticSearchClient $client, LoggerInterface $logger): FillLocationSuggestionsPlacesIndex
    {
        return new FillLocationSuggestionsPlacesIndex($client, $logger);
    }

    /**
     * @test
     */
    public function it_fills_a_new_places_index_and_swaps_it_in_for_the_previous_one(): void
    {
        $this->indices->method('exists')->willReturn(false);
        $this->indices->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $params): bool => $params['index'] === 'places_new'
                    && $params['body']['settings']['number_of_shards'] === 1
                    && isset($params['body']['mappings']['properties'])
            ));

        $this->indices->method('get')->willReturnMap([
            [['index' => 'places_write'], ['places_old' => []]],
            [['index' => 'read'], ['addresses_live' => [], 'places_old' => []]],
            [['index' => 'places_*'], ['places_old' => [], 'places_new' => []]],
        ]);

        // First the live updates, then the readers, so no place change is lost while the index fills.
        $aliasUpdates = [];
        $this->indices->expects($this->exactly(2))
            ->method('updateAliases')
            ->willReturnCallback(function (array $params) use (&$aliasUpdates): void {
                $aliasUpdates[] = $params['body']['actions'];
            });

        $this->client->expects($this->once())
            ->method('search')
            ->with($this->callback(fn (array $params): bool => $params['index'] === 'udb3_core'))
            ->willReturn([
                '_scroll_id' => 'scroll-1',
                'hits' => ['hits' => [
                    ['_id' => 'public', '_source' => ['originalEncodedJsonLd' => Json::encode(self::place('APPROVED'))]],
                    ['_id' => 'draft', '_source' => ['originalEncodedJsonLd' => Json::encode(self::place('DRAFT'))]],
                ]],
            ]);
        $this->client->method('scroll')->willReturn(['_scroll_id' => 'scroll-1', 'hits' => ['hits' => []]]);
        $this->client->expects($this->once())->method('clearScroll')->with(['scroll_id' => 'scroll-1']);

        // create and not index, so a place that changed live during the fill is not overwritten by the older copy.
        $this->client->expects($this->once())
            ->method('bulk')
            ->with($this->callback(
                fn (array $params): bool => count($params['body']) === 2
                    && $params['body'][0] === ['create' => ['_index' => 'places_new', '_id' => 'place:public']]
                    && $params['body'][1]['type'] === 'place'
            ));

        $this->indices->expects($this->once())->method('refresh')->with(['index' => 'places_new']);

        $deleted = [];
        $this->indices->method('delete')->willReturnCallback(
            function (array $params) use (&$deleted): void {
                $deleted[] = $params['index'];
            }
        );

        $this->operation->run(
            'places_new',
            'places_*',
            'read',
            'places_write',
            'udb3_core',
            new PlaceDocuments(new MunicipalityRegions([])),
            1,
            0
        );

        $this->assertSame(
            [
                [
                    ['remove' => ['index' => 'places_old', 'alias' => 'places_write']],
                    ['add' => ['index' => 'places_new', 'alias' => 'places_write', 'is_write_index' => true]],
                ],
                [
                    ['remove' => ['index' => 'places_old', 'alias' => 'read']],
                    ['add' => ['index' => 'places_new', 'alias' => 'read']],
                ],
            ],
            $aliasUpdates
        );
        $this->assertSame(['places_old'], $deleted);
    }

    /**
     * @test
     */
    public function it_fills_an_existing_places_index_without_creating_it_again(): void
    {
        $this->indices->method('exists')->willReturn(true);
        $this->indices->expects($this->never())->method('create');
        $this->indices->method('get')->willReturnMap([
            [['index' => 'places_write'], ['places_live' => []]],
            [['index' => 'read'], ['addresses_live' => [], 'places_live' => []]],
            [['index' => 'places_*'], ['places_live' => []]],
        ]);
        $this->client->method('search')->willReturn(['_scroll_id' => 'scroll-1', 'hits' => ['hits' => []]]);
        $this->indices->expects($this->never())->method('delete');

        $this->operation->run(
            'places_live',
            'places_*',
            'read',
            'places_write',
            'udb3_core',
            new PlaceDocuments(new MunicipalityRegions([])),
            1,
            0
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function place(string $workflowStatus): array
    {
        return [
            'workflowStatus' => $workflowStatus,
            'name' => ['nl' => 'Het Depot'],
            'address' => ['nl' => [
                'streetAddress' => 'Martelarenplein 12',
                'postalCode' => '3000',
                'addressLocality' => 'Leuven',
                'addressCountry' => 'BE',
            ]],
            'geo' => ['latitude' => 50.8814, 'longitude' => 4.7155],
        ];
    }
}

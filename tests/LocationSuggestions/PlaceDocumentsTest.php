<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use PHPUnit\Framework\TestCase;

final class PlaceDocumentsTest extends TestCase
{
    private PlaceDocuments $placeDocuments;

    protected function setUp(): void
    {
        $this->placeDocuments = new PlaceDocuments(new MunicipalityRegions([
            'nis-20001' => ['name' => ['nl' => 'Provincie Vlaams-Brabant'], 'children' => [
                'reg-leuven' => ['name' => ['nl' => 'Regio Leuven'], 'children' => [
                    'nis-24062' => ['name' => ['nl' => 'Leuven'], 'children' => [
                        'nis-24062C' => ['name' => ['nl' => 'Kessel-Lo']],
                    ]],
                ]],
            ]],
        ]));
    }

    /**
     * @test
     */
    public function it_prefixes_the_place_id_so_it_cannot_clash_with_an_address(): void
    {
        $this->assertSame('place:2a7a9b4c', PlaceDocuments::id('2a7a9b4c'));
    }

    /**
     * @test
     */
    public function it_creates_a_document_with_the_names_the_address_and_the_municipality_of_the_sub_municipality(): void
    {
        $this->assertSame(
            [
                'type' => 'place',
                'label' => 'De Kroeg (Lentedreef 27, 3010 Kessel-Lo)',
                'name' => ['De Kroeg', 'Le Bistrot'],
                'search_text' => 'De Kroeg Le Bistrot Lentedreef 27 3010 Kessel-Lo Leuven',
                'location' => ['lat' => 50.88958, 'lon' => 4.72023],
            ],
            $this->placeDocuments->create(self::place())
        );
    }

    /**
     * @test
     */
    public function it_cleans_up_white_space_and_drops_names_that_only_differ_in_case_or_accents(): void
    {
        $document = $this->placeDocuments->create(self::place([
            'name' => ['nl' => "Het  Depot\n", 'en' => 'het dépot'],
        ]));

        $this->assertSame(['Het Depot'], $document['name'] ?? null);
        $this->assertSame('Het Depot (Lentedreef 27, 3010 Kessel-Lo)', $document['label'] ?? null);
    }

    /**
     * @test
     * @dataProvider provideWorkflowStatuses
     */
    public function it_only_suggests_places_that_the_search_returns_by_default(string $workflowStatus, bool $suggested): void
    {
        $this->assertSame(
            $suggested,
            $this->placeDocuments->create(self::place(['workflowStatus' => $workflowStatus])) !== null
        );
    }

    public function provideWorkflowStatuses(): array
    {
        return [
            'approved' => ['APPROVED', true],
            'ready for validation' => ['READY_FOR_VALIDATION', true],
            'draft' => ['DRAFT', false],
            'rejected' => ['REJECTED', false],
            'deleted' => ['DELETED', false],
        ];
    }

    /**
     * @test
     * @dataProvider provideUnsuggestedPlaces
     */
    public function it_does_not_suggest_a_place_it_cannot_filter_on(array $override): void
    {
        $this->assertNull($this->placeDocuments->create(self::place($override)));
    }

    public function provideUnsuggestedPlaces(): array
    {
        return [
            'no coordinates' => [['geo' => null]],
            'no name' => [['name' => ['nl' => ' ']]],
            'no address' => [['address' => null]],
            'abroad' => [['address' => ['nl' => [
                'streetAddress' => 'Rue de Rivoli 1',
                'postalCode' => '75001',
                'addressLocality' => 'Paris',
                'addressCountry' => 'FR',
            ]]]],
        ];
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private static function place(array $override = []): array
    {
        return array_merge(
            [
                'workflowStatus' => 'APPROVED',
                'mainLanguage' => 'nl',
                'name' => ['nl' => 'De Kroeg', 'fr' => 'Le Bistrot'],
                'address' => ['nl' => [
                    'streetAddress' => 'Lentedreef 27',
                    'postalCode' => '3010',
                    'addressLocality' => 'Kessel-Lo',
                    'addressCountry' => 'BE',
                ]],
                'geo' => ['latitude' => 50.88958, 'longitude' => 4.72023],
            ],
            $override
        );
    }
}

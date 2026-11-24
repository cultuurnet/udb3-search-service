<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use PHPUnit\Framework\TestCase;

final class OpenAddressDocumentsTest extends TestCase
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $documents;

    protected function setUp(): void
    {
        $this->documents = iterator_to_array(
            new OpenAddressDocuments(new OpenAddressCsvReader([__DIR__ . '/data/openaddress.csv']), self::regions())
        );
    }

    /**
     * @test
     */
    public function it_creates_an_address_document_per_house_number(): void
    {
        $this->assertSame(
            [
                'type' => 'address',
                'label' => 'Lentedreef 27, 3010 Kessel-Lo',
                'search_text' => 'Lentedreef 3010 Kessel-Lo Leuven Louvain Löwen 27',
                'location' => ['lat' => 50.88958, 'lon' => 4.72023],
            ],
            $this->documents['address:BE-VLG:35063:3010:27']
        );
    }

    /**
     * @test
     */
    public function it_creates_a_street_document_with_the_average_coordinate_of_its_addresses(): void
    {
        $this->assertSame(
            [
                'type' => 'street',
                'label' => 'Lentedreef, 3010 Kessel-Lo',
                'name' => ['Lentedreef'],
                'search_text' => 'Lentedreef 3010 Kessel-Lo Leuven Louvain Löwen',
                'location' => ['lat' => 50.88943, 'lon' => 4.72002],
                'address_count' => 2,
            ],
            $this->documents['street:BE-VLG:35063:3010']
        );
    }

    /**
     * @test
     */
    public function it_creates_a_municipality_document_with_its_region_and_the_addresses_of_all_its_streets(): void
    {
        $this->assertSame(
            [
                'type' => 'municipality',
                'label' => 'Leuven',
                'name' => ['Leuven', 'Louvain', 'Löwen'],
                'search_text' => 'Leuven Louvain Löwen',
                'regions' => ['nis-24062'],
                'location' => ['lat' => 50.88943, 'lon' => 4.72002],
                'address_count' => 2,
            ],
            $this->documents['municipality:24062']
        );
    }

    /**
     * @test
     */
    public function it_creates_a_sub_municipality_document_with_its_region_and_postcode(): void
    {
        $this->assertSame(
            [
                'type' => 'submunicipality',
                'label' => 'Kessel-Lo (3010)',
                'name' => ['Kessel-Lo', '3010'],
                'search_text' => 'Kessel-Lo 3010',
                'regions' => ['nis-24062C'],
                'location' => ['lat' => 50.88943, 'lon' => 4.72002],
                'address_count' => 2,
            ],
            $this->documents['submunicipality:nis-24062C']
        );
    }

    /**
     * @test
     */
    public function it_gives_every_sub_municipality_of_a_shared_postname_its_share_of_the_addresses(): void
    {
        $this->assertSame(1, $this->documents['submunicipality:nis-44021F']['address_count']);
        $this->assertSame(1, $this->documents['submunicipality:nis-44021G']['address_count']);
        $this->assertSame('Ledeberg (9050)', $this->documents['submunicipality:nis-44021G']['label']);
    }

    /**
     * @test
     */
    public function it_finds_a_sub_municipality_on_all_its_postcodes_and_labels_it_with_the_first(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['municipality_id' => '24062', 'municipality_name_nl' => 'Leuven', 'postcode' => '3011', 'postname_nl' => 'Kessel-Lo']),
            self::row(['municipality_id' => '24062', 'municipality_name_nl' => 'Leuven', 'postcode' => '3010', 'postname_nl' => 'Kessel-Lo']),
        ], self::regions()));

        $this->assertSame('Kessel-Lo (3010)', $documents['submunicipality:nis-24062C']['label']);
        $this->assertSame(['Kessel-Lo', '3010', '3011'], $documents['submunicipality:nis-24062C']['name']);
    }

    /**
     * @test
     */
    public function it_creates_no_sub_municipality_for_a_postname_without_a_region(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['municipality_id' => '24062', 'municipality_name_nl' => 'Leuven', 'postcode' => '3018', 'postname_nl' => 'Wijgmaal']),
        ], self::regions()));

        $this->assertArrayHasKey('municipality:24062', $documents);
        $this->assertSame([], preg_grep('/^submunicipality:/', array_keys($documents)));
    }

    /**
     * @test
     */
    public function it_counts_the_addresses_of_every_street_in_a_municipality(): void
    {
        $this->assertSame(3, $this->documents['municipality:44021']['address_count']);
    }

    /**
     * @test
     */
    public function it_leaves_out_a_municipality_without_a_region_because_its_filter_would_find_nothing(): void
    {
        $this->assertArrayNotHasKey('municipality:24137', $this->documents);
    }

    /**
     * @test
     */
    public function it_shows_the_municipality_when_the_postcode_covers_several_sub_municipalities(): void
    {
        $this->assertSame('Kerkstraat, 9050 Gent', $this->documents['street:BE-VLG:70662:9050']['label']);
    }

    /**
     * @test
     */
    public function it_shows_the_municipality_instead_of_a_postname_in_capitals(): void
    {
        $this->assertSame('Veldstraat, 9000 Gent', $this->documents['street:BE-VLG:71854:9000']['label']);
    }

    /**
     * @test
     */
    public function it_uses_french_names_in_wallonia(): void
    {
        $this->assertSame('Rue des Brasseurs 16, 4000 Liège', $this->documents['address:BE-WAL:7730567:4000:16']['label']);
    }

    /**
     * @test
     */
    public function it_indexes_a_house_number_with_box_numbers_once(): void
    {
        $henegouwenkaai = array_filter(
            array_keys($this->documents),
            static fn (string $id): bool => str_starts_with($id, 'address:BE-BRU:3996:1080:29')
        );

        $this->assertCount(1, $henegouwenkaai);
    }

    /**
     * @test
     */
    public function it_counts_a_house_number_with_box_numbers_once(): void
    {
        $this->assertSame(1, $this->documents['street:BE-BRU:3996:1080']['address_count']);
    }

    /**
     * @test
     */
    public function it_skips_addresses_that_are_not_current(): void
    {
        $this->assertArrayNotHasKey('street:BE-VLG:35250:3010', $this->documents);
    }

    /**
     * @test
     */
    public function it_takes_the_postname_in_the_same_language_as_the_street_name(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['region_code' => 'BE-WAL', 'streetname_fr' => 'Rue Haute', 'postname_fr' => 'Jupille', 'postname_nl' => 'Jupille-aan-de-Maas']),
        ], self::regions()));

        $this->assertSame('Rue Haute, 4020 Jupille', $documents['street:BE-WAL:1:4020']['label']);
    }

    /**
     * @test
     */
    public function it_shows_the_municipality_instead_of_a_postname_in_capitals_with_accents(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['region_code' => 'BE-WAL', 'streetname_fr' => 'Rue Haute', 'postname_fr' => 'LIÈGE', 'municipality_name_fr' => 'Liège']),
        ], self::regions()));

        $this->assertSame('Rue Haute, 4020 Liège', $documents['street:BE-WAL:1:4020']['label']);
    }

    /**
     * @test
     */
    public function it_keeps_one_spelling_of_words_that_only_differ_in_accents_or_case(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['streetname_fr' => 'Rue Haute', 'postname_fr' => 'LIEGE', 'municipality_name_fr' => 'Liège']),
        ], self::regions()));

        $this->assertSame('Rue Haute 4020 LIEGE', $documents['street:BE-WAL:1:4020']['search_text']);
    }

    /**
     * @test
     */
    public function it_names_a_street_in_every_language_of_the_csv(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['region_code' => 'BE-BRU', 'streetname_nl' => 'Henegouwenkaai', 'streetname_fr' => 'Quai du Hainaut']),
        ], self::regions()));

        $this->assertSame(['Henegouwenkaai', 'Quai du Hainaut'], $documents['street:BE-BRU:1:4020']['name']);
    }

    private static function regions(): MunicipalityRegions
    {
        return new MunicipalityRegions([
            'nis-20001' => ['name' => ['nl' => 'Provincie Vlaams-Brabant'], 'children' => [
                'reg-leuven' => ['name' => ['nl' => 'Regio Leuven'], 'children' => [
                    'nis-24062' => ['name' => ['nl' => 'Leuven'], 'children' => [
                        'nis-24062C' => ['name' => ['nl' => 'Kessel-Lo']],
                    ]],
                ]],
            ]],
            'nis-40000' => ['name' => ['nl' => 'Provincie Oost-Vlaanderen'], 'children' => [
                'reg-gent' => ['name' => ['nl' => 'Regio Gent'], 'children' => [
                    'nis-44021' => ['name' => ['nl' => 'Gent'], 'children' => [
                        'nis-44021F' => ['name' => ['nl' => 'Gentbrugge']],
                        'nis-44021G' => ['name' => ['nl' => 'Ledeberg']],
                    ]],
                ]],
            ]],
        ]);
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private static function row(array $values): array
    {
        return array_merge([
            'EPSG:4326_lat' => '50.6',
            'EPSG:4326_lon' => '5.6',
            'house_number' => '1',
            'municipality_name_nl' => '',
            'municipality_name_fr' => 'Liège',
            'municipality_name_de' => '',
            'municipality_id' => '62063',
            'postcode' => '4020',
            'postname_nl' => '',
            'postname_fr' => '',
            'street_id' => '1',
            'streetname_nl' => '',
            'streetname_fr' => '',
            'streetname_de' => '',
            'region_code' => 'BE-WAL',
            'status' => 'current',
        ], $values);
    }
}

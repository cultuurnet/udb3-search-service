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
            new OpenAddressDocuments(new OpenAddressCsvReader([__DIR__ . '/data/openaddress.csv']))
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
                'search_text' => 'Lentedreef 3010 Kessel-Lo Leuven Louvain Löwen',
                'location' => ['lat' => 50.88943, 'lon' => 4.72002],
            ],
            $this->documents['street:BE-VLG:35063:3010']
        );
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
        ]));

        $this->assertSame('Rue Haute, 4020 Jupille', $documents['street:BE-WAL:1:4020']['label']);
    }

    /**
     * @test
     */
    public function it_shows_the_municipality_instead_of_a_postname_in_capitals_with_accents(): void
    {
        $documents = iterator_to_array(new OpenAddressDocuments([
            self::row(['region_code' => 'BE-WAL', 'streetname_fr' => 'Rue Haute', 'postname_fr' => 'LIÈGE', 'municipality_name_fr' => 'Liège']),
        ]));

        $this->assertSame('Rue Haute, 4020 Liège', $documents['street:BE-WAL:1:4020']['label']);
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

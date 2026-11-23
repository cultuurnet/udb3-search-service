<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use PHPUnit\Framework\TestCase;

final class MunicipalityRegionsTest extends TestCase
{
    private MunicipalityRegions $regions;

    protected function setUp(): void
    {
        // Shaped like facet_mapping_regions.php: province, region, municipality, sub-municipality.
        $this->regions = new MunicipalityRegions([
            'nis-20001' => [
                'name' => ['nl' => 'Provincie Vlaams-Brabant'],
                'children' => [
                    'reg-leuven' => [
                        'name' => ['nl' => 'Regio Leuven'],
                        'children' => [
                            'nis-24062' => [
                                'name' => ['nl' => 'Leuven'],
                                'children' => [
                                    'nis-24062C' => ['name' => ['nl' => 'Kessel-Lo']],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'nis-50000' => [
                'name' => ['nl' => 'Provincie Henegouwen'],
                'children' => [
                    'reg-wallonie-picarde' => [
                        'name' => ['nl' => 'Wallonie picarde'],
                        'children' => [
                            'nis-55010' => ['name' => ['nl' => 'Edingen', 'fr' => 'Enghien']],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_finds_a_municipality_by_its_nis_code(): void
    {
        $this->assertSame('nis-24062', $this->regions->find('24062', ['Leuven', 'Louvain']));
    }

    /**
     * @test
     */
    public function it_finds_a_municipality_that_got_a_new_nis_code_by_its_name(): void
    {
        $this->assertSame('nis-55010', $this->regions->find('51067', ['Enghien']));
    }

    /**
     * @test
     */
    public function it_ignores_capitals_and_accents_in_the_name(): void
    {
        $this->assertSame('nis-55010', $this->regions->find('51067', ['ENGHÏEN']));
    }

    /**
     * @test
     */
    public function it_only_finds_municipalities_and_not_their_sub_municipalities(): void
    {
        $this->assertNull($this->regions->find('99999', ['Kessel-Lo']));
    }

    /**
     * @test
     */
    public function it_finds_nothing_for_an_unknown_municipality(): void
    {
        $this->assertNull($this->regions->find('99999', ['Nergensdorp']));
    }
}

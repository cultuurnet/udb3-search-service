<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use function Symfony\Component\String\u;

/**
 * Finds the Search API 3 region of a municipality and its sub-municipalities in facet_mapping_regions.php
 * (from cultuurnet/geojson-data), so their regions filter matches the regions of events and places.
 */
final readonly class MunicipalityRegions
{
    /**
     * @var array<string, true>
     */
    private array $ids;

    /**
     * @var array<string, string>
     */
    private array $idsByName;

    /**
     * @var array<string, array<string, array{id: string, names: list<string>}>>
     */
    private array $subMunicipalitiesByName;

    /**
     * @param array<string, array<string, mixed>> $facetMappingRegions
     */
    public function __construct(array $facetMappingRegions)
    {
        $ids = [];
        $idsByName = [];
        $subMunicipalitiesByName = [];

        // The tree goes province, region, municipality, sub-municipality.
        foreach ($facetMappingRegions as $province) {
            foreach ($province['children'] ?? [] as $region) {
                foreach ($region['children'] ?? [] as $id => $municipality) {
                    $ids[$id] = true;
                    foreach ($municipality['name'] ?? [] as $name) {
                        $idsByName[self::fold($name)] = $id;
                    }
                    foreach ($municipality['children'] ?? [] as $subId => $subMunicipality) {
                        $names = array_values($subMunicipality['name'] ?? []);
                        foreach ($names as $name) {
                            $subMunicipalitiesByName[$id][self::fold($name)] = ['id' => $subId, 'names' => $names];
                        }
                    }
                }
            }
        }

        $this->ids = $ids;
        $this->idsByName = $idsByName;
        $this->subMunicipalitiesByName = $subMunicipalitiesByName;
    }

    /**
     * @param list<string> $names
     */
    public function find(string $nisCode, array $names): ?string
    {
        if (isset($this->ids['nis-' . $nisCode])) {
            return 'nis-' . $nisCode;
        }

        // geojson-data still has the NIS codes from before 2019 for eleven municipalities in Hainaut,
        // while the address CSV has the new ones (Enghien: 55010 against 51067). Their name still matches.
        foreach ($names as $name) {
            if (isset($this->idsByName[self::fold($name)])) {
                return $this->idsByName[self::fold($name)];
            }
        }

        return null;
    }

    /**
     * The address CSV has no sub-municipality, only the postname of the postcode, which is the name of a sub-municipality
     * in most cases (3010 Kessel-Lo). Wijgmaal is a postname without its own region, and the region list also has
     * combined names like Berendrecht-Zandvliet-Lillo, so not every postname or sub-municipality finds the other.
     *
     * @return array{id: string, names: list<string>}|null
     */
    public function findSubMunicipalityByPostname(string $municipalityRegion, string $postname): ?array
    {
        return $this->subMunicipalitiesByName[$municipalityRegion][self::fold($postname)] ?? null;
    }

    private static function fold(string $name): string
    {
        return u($name)->ascii()->lower()->toString();
    }
}

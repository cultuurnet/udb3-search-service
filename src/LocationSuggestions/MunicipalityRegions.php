<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use function Symfony\Component\String\u;

/**
 * Finds the Search API 3 region of a municipality in facet_mapping_regions.php (from cultuurnet/geojson-data),
 * so its regions filter matches the regions of events and places.
 */
final class MunicipalityRegions
{
    /**
     * @var array<string, true>
     */
    private array $ids = [];

    /**
     * @var array<string, string>
     */
    private array $idsByName = [];

    /**
     * @param array<string, array<string, mixed>> $facetMappingRegions
     */
    public function __construct(array $facetMappingRegions)
    {
        // The tree goes province, region, municipality, sub-municipality.
        foreach ($facetMappingRegions as $province) {
            foreach ($province['children'] ?? [] as $region) {
                foreach ($region['children'] ?? [] as $id => $municipality) {
                    $this->ids[$id] = true;
                    foreach ($municipality['name'] ?? [] as $name) {
                        $this->idsByName[self::fold($name)] = $id;
                    }
                }
            }
        }
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

    private static function fold(string $name): string
    {
        return u($name)->ascii()->lower()->toString();
    }
}

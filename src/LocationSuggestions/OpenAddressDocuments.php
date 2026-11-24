<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use Generator;
use IteratorAggregate;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Symfony\Component\String\u;

/**
 * Every row of the address CSV is one address. It becomes an address document, and it counts towards:
 * - its street (street + postcode),
 * - its municipality (municipality_id, with the region from the region list),
 * - its sub-municipalities (the postname, matched to a sub-municipality of the municipality in the region list).
 * Streets, municipalities and sub-municipalities get the number of their addresses and the average coordinate.
 *
 * @implements IteratorAggregate<string, array<string, mixed>>
 */
final readonly class OpenAddressDocuments implements IteratorAggregate
{
    /**
     * @param iterable<array<string, string>> $rows
     */
    public function __construct(
        private iterable $rows,
        private MunicipalityRegions $municipalityRegions,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @return Generator<string, array<string, mixed>>
     */
    public function getIterator(): Generator
    {
        $groups = [];
        $seenAddressIds = [];
        $municipalityRegions = [];
        $subMunicipalities = [];
        $postcodes = [];

        foreach ($this->rows as $row) {
            // Every box number of a house number is its own row. The building is one address,
            // and counting each flat would pull the street's coordinate towards large blocks.
            $addressId = implode(':', ['address', $row['region_code'], $row['street_id'], $row['postcode'], $row['house_number']]);
            if (isset($seenAddressIds[$addressId])) {
                continue;
            }
            $seenAddressIds[$addressId] = true;

            $streetName = $this->name($row, 'streetname');
            $postcode = $row['postcode'];
            $locality = $this->locality($row);
            $words = $this->searchWords($row);
            $lat = (float) $row['EPSG:4326_lat'];
            $lon = (float) $row['EPSG:4326_lon'];

            yield $addressId => [
                'type' => 'address',
                'label' => sprintf('%s %s, %s %s', $streetName, $row['house_number'], $postcode, $locality),
                'search_text' => implode(' ', $this->unique([...$words, $row['house_number']])),
                'location' => ['lat' => $lat, 'lon' => $lon],
            ];

            // A street that crosses a postcode border gets one document per postcode.
            $this->addToGroup($groups, implode(':', ['street', $row['region_code'], $row['street_id'], $postcode]), $lat, $lon, fn (): array => [
                'type' => 'street',
                'label' => sprintf('%s, %s %s', $streetName, $postcode, $locality),
                // Not on addresses: with a house number typed, the text is never just a name.
                'name' => $this->names($row, 'streetname'),
                'search_text' => implode(' ', $this->unique($words)),
            ]);

            $municipalityId = $row['municipality_id'];
            if (!array_key_exists($municipalityId, $municipalityRegions)) {
                $municipalityRegions[$municipalityId] = $this->municipalityRegion($row);
            }
            // Without a region its filter would find nothing in Search API 3, so it isn't suggested.
            if ($municipalityRegions[$municipalityId] !== null) {
                $this->addToGroup($groups, 'municipality:' . $municipalityId, $lat, $lon, fn (): array => [
                    'type' => 'municipality',
                    'label' => $this->name($row, 'municipality_name'),
                    'name' => $this->names($row, 'municipality_name'),
                    'search_text' => implode(' ', $this->names($row, 'municipality_name')),
                    'regions' => [$municipalityRegions[$municipalityId]],
                ]);

                $postnameKey = implode('|', [$municipalityId, $row['postname_nl'], $row['postname_fr']]);
                $subMunicipalities[$postnameKey] ??= $this->subMunicipalities($row, $municipalityRegions[$municipalityId]);
                foreach ($subMunicipalities[$postnameKey] as $region => $names) {
                    // A postname shared by several sub-municipalities (Averbode/Zichem) doesn't say which one an address is in,
                    // so each of them counts its share. That also keeps a sub-municipality below its municipality.
                    // The label and search text need every postcode, so withPostcodes() fills them in after the last row.
                    $this->addToGroup($groups, 'submunicipality:' . $region, $lat, $lon, fn (): array => [
                        'type' => 'submunicipality',
                        'label' => '',
                        'name' => $names,
                        'search_text' => '',
                        'regions' => [$region],
                    ], 1 / count($subMunicipalities[$postnameKey]));
                    $postcodes['submunicipality:' . $region][$row['postcode']] = true;
                }
            }
        }

        // A street's or municipality's coordinate is the average of all its addresses, so they can only follow the last row.
        foreach ($groups as $id => $group) {
            $document = isset($postcodes[$id]) ? $this->withPostcodes($group['document'], array_keys($postcodes[$id])) : $group['document'];

            yield $id => [
                ...$document,
                'location' => [
                    'lat' => round($group['lat_sum'] / $group['count'], 5),
                    'lon' => round($group['lon_sum'] / $group['count'], 5),
                ],
                'address_count' => max(1, (int) round($group['address_count'])),
            ];
        }
    }

    /**
     * @param array<string, string> $row
     * @return array<string, list<string>> The names of the sub-municipalities in the postname of the row, by region.
     */
    private function subMunicipalities(array $row, string $municipalityRegion): array
    {
        $subMunicipalities = [];
        foreach ([...explode('/', $row['postname_nl']), ...explode('/', $row['postname_fr'])] as $postname) {
            $subMunicipality = $postname !== '' ? $this->municipalityRegions->findSubMunicipalityByPostname($municipalityRegion, $postname) : null;
            if ($subMunicipality !== null) {
                $subMunicipalities[$subMunicipality['id']] = $subMunicipality['names'];
            }
        }

        return $subMunicipalities;
    }

    /**
     * @param array<string, mixed> $document
     * @param list<int|string> $postcodes
     * @return array<string, mixed>
     */
    private function withPostcodes(array $document, array $postcodes): array
    {
        // Array keys turn postcodes into integers.
        $postcodes = array_map('strval', $postcodes);
        sort($postcodes);
        $names = [...$document['name'], ...$postcodes];

        // Typing the postcode finds the sub-municipality. Antwerpen has seven, the label shows the first.
        return [
            ...$document,
            'label' => sprintf('%s (%s)', $document['name'][0], $postcodes[0]),
            'name' => $names,
            'search_text' => implode(' ', $names),
        ];
    }

    /**
     * @param array<string, array{document: array<string, mixed>, lat_sum: float, lon_sum: float, count: int, address_count: float}> $groups
     * @param callable(): array<string, mixed> $document Only called for the first address of the group.
     * @param float $share How much of the address belongs to the group.
     */
    private function addToGroup(array &$groups, string $id, float $lat, float $lon, callable $document, float $share = 1.0): void
    {
        $groups[$id] ??= ['document' => $document(), 'lat_sum' => 0.0, 'lon_sum' => 0.0, 'count' => 0, 'address_count' => 0.0];
        $groups[$id]['lat_sum'] += $lat;
        $groups[$id]['lon_sum'] += $lon;
        $groups[$id]['count']++;
        $groups[$id]['address_count'] += $share;
    }

    /**
     * @param array<string, string> $row
     */
    private function municipalityRegion(array $row): ?string
    {
        $names = $this->names($row, 'municipality_name');
        $region = $this->municipalityRegions->find($row['municipality_id'], $names);
        if ($region === null) {
            $this->logger->warning(sprintf('No region for municipality %s (%s), left out.', $row['municipality_id'], implode(', ', $names)));
        }

        return $region;
    }

    /**
     * @param array<string, string> $row
     */
    private function name(array $row, string $column): string
    {
        foreach ($this->languages($row) as $language) {
            // The CSV has no German postname column.
            $name = $row[$column . '_' . $language] ?? '';
            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    /**
     * @param array<string, string> $row
     * @return list<string>
     */
    private function languages(array $row): array
    {
        // Wallonia has French names, often without a Dutch one. Elsewhere UiTinVlaanderen shows Dutch.
        return $row['region_code'] === 'BE-WAL' ? ['fr', 'de', 'nl'] : ['nl', 'fr', 'de'];
    }

    /**
     * @param array<string, string> $row
     */
    private function locality(array $row): string
    {
        $municipality = $this->name($row, 'municipality_name');
        $postnames = $this->postnames($row);

        // A postcode can cover several sub-municipalities (Gentbrugge/Ledeberg), and the
        // CSV often repeats the municipality in capitals (GENT). The municipality reads better then.
        if (count($postnames) === 1 && mb_strtolower($postnames[0]) !== mb_strtolower($municipality)) {
            return $postnames[0];
        }

        return $municipality;
    }

    /**
     * @param array<string, string> $row
     * @return list<string>
     */
    private function postnames(array $row): array
    {
        return array_values(array_filter(explode('/', $this->name($row, 'postname')), static fn (string $name): bool => $name !== ''));
    }

    /**
     * @param array<string, string> $row
     * @return list<string>
     */
    private function names(array $row, string $column): array
    {
        // Brussels streets and many municipalities have a name in several languages, and typing any of them is an exact match.
        return $this->unique(array_map(static fn (string $language): string => $row[$column . '_' . $language] ?? '', $this->languages($row)));
    }

    /**
     * @param array<string, string> $row
     * @return list<string>
     */
    private function searchWords(array $row): array
    {
        return [
            $row['streetname_nl'],
            $row['streetname_fr'],
            $row['streetname_de'],
            $row['postcode'],
            ...explode('/', $row['postname_nl']),
            ...explode('/', $row['postname_fr']),
            $row['municipality_name_nl'],
            $row['municipality_name_fr'],
            $row['municipality_name_de'],
        ];
    }

    /**
     * @param list<string> $words
     * @return list<string>
     */
    private function unique(array $words): array
    {
        $unique = [];
        foreach ($words as $word) {
            if ($word !== '') {
                // Folded like the location_analyzer does, so Liège and LIEGE end up as one word.
                $unique[u($word)->ascii()->lower()->toString()] ??= $word;
            }
        }

        return array_values($unique);
    }
}

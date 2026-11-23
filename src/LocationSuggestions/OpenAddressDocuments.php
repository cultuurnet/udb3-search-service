<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use Generator;
use IteratorAggregate;

use function Symfony\Component\String\u;

/**
 * @implements IteratorAggregate<string, array<string, mixed>>
 */
final class OpenAddressDocuments implements IteratorAggregate
{
    /**
     * @param iterable<array<string, string>> $rows
     */
    public function __construct(private readonly iterable $rows)
    {
    }

    /**
     * @return Generator<string, array<string, mixed>>
     */
    public function getIterator(): Generator
    {
        $streets = [];
        $seenAddressIds = [];

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
            $names = $this->streetNames($row);
            $words = $this->searchWords($row);
            $lat = (float) $row['EPSG:4326_lat'];
            $lon = (float) $row['EPSG:4326_lon'];

            yield $addressId => [
                'type' => 'address',
                'label' => sprintf('%s %s, %s %s', $streetName, $row['house_number'], $postcode, $locality),
                'name' => $names,
                'search_text' => implode(' ', $this->unique([...$words, $row['house_number']])),
                'location' => ['lat' => $lat, 'lon' => $lon],
            ];

            // A street that crosses a postcode border gets one document per postcode.
            $streetId = implode(':', ['street', $row['region_code'], $row['street_id'], $postcode]);
            $streets[$streetId] ??= [
                'label' => sprintf('%s, %s %s', $streetName, $postcode, $locality),
                'name' => $names,
                'search_text' => implode(' ', $this->unique($words)),
                'lat_sum' => 0.0,
                'lon_sum' => 0.0,
                'count' => 0,
            ];
            $streets[$streetId]['lat_sum'] += $lat;
            $streets[$streetId]['lon_sum'] += $lon;
            $streets[$streetId]['count']++;
        }

        // A street's coordinate is the average of all its addresses, so streets can only follow the last row.
        foreach ($streets as $streetId => $street) {
            yield $streetId => [
                'type' => 'street',
                'label' => $street['label'],
                'name' => $street['name'],
                'search_text' => $street['search_text'],
                'location' => [
                    'lat' => round($street['lat_sum'] / $street['count'], 5),
                    'lon' => round($street['lon_sum'] / $street['count'], 5),
                ],
            ];
        }
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
    private function streetNames(array $row): array
    {
        // Brussels streets have a Dutch and a French name, and typing either one is an exact match.
        return $this->unique(array_map(static fn (string $language): string => $row['streetname_' . $language], $this->languages($row)));
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

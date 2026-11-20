<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use Generator;
use RuntimeException;

final class OpenAddressCsvReader
{
    // Only the columns we read are checked. BOSA renamed the projected coordinate
    // columns before, and a column we ignore should not stop the indexing.
    private const REQUIRED_COLUMNS = [
        'EPSG:4326_lat',
        'EPSG:4326_lon',
        'house_number',
        'municipality_name_nl',
        'municipality_name_fr',
        'municipality_name_de',
        'postcode',
        'postname_nl',
        'postname_fr',
        'street_id',
        'streetname_nl',
        'streetname_fr',
        'streetname_de',
        'region_code',
        'status',
    ];

    /**
     * @return Generator<array<string, string>>
     */
    public static function rows(string $path): Generator
    {
        if (!is_readable($path)) {
            throw new RuntimeException('Could not open ' . $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Could not open ' . $path);
        }

        try {
            $header = self::readLine($handle);
            if ($header === null) {
                throw new RuntimeException($path . ' is empty');
            }

            $missingColumns = array_diff(self::REQUIRED_COLUMNS, $header);
            if ($missingColumns !== []) {
                throw new RuntimeException($path . ' misses the columns ' . implode(', ', $missingColumns));
            }

            while (($values = self::readLine($handle)) !== null) {
                if (count($values) !== count($header)) {
                    continue;
                }

                $row = array_combine($header, $values);
                if (self::isUsable($row)) {
                    yield $row;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return list<string>|null
     */
    private static function readLine($handle): ?array
    {
        $values = fgetcsv($handle, null, ',', '"', '');
        if ($values === false) {
            return null;
        }

        return array_map(static fn (?string $value): string => $value ?? '', $values);
    }

    /**
     * @param array<string, string> $row
     */
    private static function isUsable(array $row): bool
    {
        // Retired, proposed and rejected addresses do not exist on the street today.
        return $row['status'] === 'current'
            && $row['postcode'] !== ''
            && $row['EPSG:4326_lat'] !== ''
            && $row['EPSG:4326_lon'] !== '';
    }
}

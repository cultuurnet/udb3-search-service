<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use Generator;
use IteratorAggregate;
use RuntimeException;

/**
 * @implements IteratorAggregate<int, array<string, string>>
 */
final class OpenAddressCsvReader implements IteratorAggregate
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
     * @param list<string> $paths
     */
    public function __construct(private readonly array $paths)
    {
    }

    /**
     * @return Generator<int, array<string, string>>
     */
    public function getIterator(): Generator
    {
        foreach ($this->paths as $path) {
            yield from $this->rows($path);
        }
    }

    /**
     * @return Generator<int, array<string, string>>
     */
    private function rows(string $path): Generator
    {
        if (!is_readable($path)) {
            throw new RuntimeException('Could not open ' . $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Could not open ' . $path);
        }

        try {
            $header = $this->readLine($handle);
            if ($header === null) {
                throw new RuntimeException($path . ' is empty');
            }

            $missingColumns = array_diff(self::REQUIRED_COLUMNS, $header);
            if ($missingColumns !== []) {
                throw new RuntimeException($path . ' misses the columns ' . implode(', ', $missingColumns));
            }

            while (($values = $this->readLine($handle)) !== null) {
                if (count($values) !== count($header)) {
                    continue;
                }

                $row = array_combine($header, $values);
                if ($this->isUsable($row)) {
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
    private function readLine($handle): ?array
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
    private function isUsable(array $row): bool
    {
        // Retired, proposed and rejected addresses do not exist on the street today.
        return $row['status'] === 'current'
            && $row['postcode'] !== ''
            && $row['EPSG:4326_lat'] !== ''
            && $row['EPSG:4326_lon'] !== '';
    }
}

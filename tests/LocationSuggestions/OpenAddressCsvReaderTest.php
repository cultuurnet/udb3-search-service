<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenAddressCsvReaderTest extends TestCase
{
    /**
     * @test
     */
    public function it_reads_only_current_addresses(): void
    {
        $rows = iterator_to_array(OpenAddressCsvReader::rows(__DIR__ . '/data/openaddress.csv'), false);

        $this->assertCount(10, $rows);
        $this->assertSame(['current'], array_values(array_unique(array_column($rows, 'status'))));
    }

    /**
     * @test
     */
    public function it_refuses_a_file_without_the_columns_it_reads(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'addresses');
        file_put_contents((string) $path, "address_id,house_number\n1,12\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/misses the columns EPSG:4326_lat/');

        try {
            iterator_to_array(OpenAddressCsvReader::rows((string) $path));
        } finally {
            unlink((string) $path);
        }
    }
}

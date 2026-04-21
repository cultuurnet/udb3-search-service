<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use PHPUnit\Framework\TestCase;

final class SchemaVersionsTest extends TestCase
{
    private const MAPPING_DIR = __DIR__ . '/../../../src/ElasticSearch/Operations/json/';

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_udb3_core_mappings(): void
    {
        $this->assertSame(
            SchemaVersions::UDB3_CORE,
            md5(
                file_get_contents(self::MAPPING_DIR . 'mapping_udb3_core.json') .
                file_get_contents(self::MAPPING_DIR . 'mapping_event.json') .
                file_get_contents(self::MAPPING_DIR . 'mapping_place.json') .
                file_get_contents(self::MAPPING_DIR . 'mapping_organizer.json')
            ),
            'One or more udb3_core mapping files have changed. Run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_geoshapes_mappings(): void
    {
        $this->assertSame(
            SchemaVersions::GEOSHAPES,
            md5(file_get_contents(self::MAPPING_DIR . 'mapping_region.json')),
            'mapping_region.json has changed. Run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }
}

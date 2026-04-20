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
    public function it_has_the_latest_udb3_core_version(): void
    {
        $this->assertTrue(defined(SchemaVersions::class . '::UDB3_CORE'));
    }

    /**
     * @test
     */
    public function it_has_the_latest_geoshapes_version(): void
    {
        $this->assertTrue(defined(SchemaVersions::class . '::GEOSHAPES'));
    }

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_udb3_core_mapping(): void
    {
        $contents = file_get_contents(self::MAPPING_DIR . 'mapping_udb3_core.json');
        $this->assertNotFalse($contents, 'Could not read mapping_udb3_core.json');
        $this->assertSame(
            SchemaVersions::UDB3_CORE_MAPPING_HASH,
            md5($contents . SchemaVersions::UDB3_CORE),
            'mapping_udb3_core.json has changed. Update SchemaVersions::UDB3_CORE and run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_event_mapping(): void
    {
        $contents = file_get_contents(self::MAPPING_DIR . 'mapping_event.json');
        $this->assertNotFalse($contents, 'Could not read mapping_event.json');
        $this->assertSame(
            SchemaVersions::EVENT_MAPPING_HASH,
            md5($contents . SchemaVersions::UDB3_CORE),
            'mapping_event.json has changed. Update SchemaVersions::UDB3_CORE and run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_place_mapping(): void
    {
        $contents = file_get_contents(self::MAPPING_DIR . 'mapping_place.json');
        $this->assertNotFalse($contents, 'Could not read mapping_place.json');
        $this->assertSame(
            SchemaVersions::PLACE_MAPPING_HASH,
            md5($contents . SchemaVersions::UDB3_CORE),
            'mapping_place.json has changed. Update SchemaVersions::UDB3_CORE and run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_organizer_mapping(): void
    {
        $contents = file_get_contents(self::MAPPING_DIR . 'mapping_organizer.json');
        $this->assertNotFalse($contents, 'Could not read mapping_organizer.json');
        $this->assertSame(
            SchemaVersions::ORGANIZER_MAPPING_HASH,
            md5($contents . SchemaVersions::UDB3_CORE),
            'mapping_organizer.json has changed. Update SchemaVersions::UDB3_CORE and run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }

    /**
     * @test
     */
    public function it_has_a_matching_hash_for_region_mapping(): void
    {
        $contents = file_get_contents(self::MAPPING_DIR . 'mapping_region.json');
        $this->assertNotFalse($contents, 'Could not read mapping_region.json');
        $this->assertSame(
            SchemaVersions::REGION_MAPPING_HASH,
            md5($contents . SchemaVersions::GEOSHAPES),
            'mapping_region.json has changed. Update SchemaVersions::GEOSHAPES and run bin/generate-schema-hashes.php to get the new hash values.'
        );
    }
}

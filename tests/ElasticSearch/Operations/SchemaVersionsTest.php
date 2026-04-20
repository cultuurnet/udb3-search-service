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

    public function mappingHashDataProvider(): array
    {
        return [
            'udb3_core' => ['mapping_udb3_core.json', SchemaVersions::UDB3_CORE, SchemaVersions::UDB3_CORE_MAPPING_HASH, 'UDB3_CORE'],
            'event'     => ['mapping_event.json',     SchemaVersions::UDB3_CORE, SchemaVersions::EVENT_MAPPING_HASH,     'UDB3_CORE'],
            'place'     => ['mapping_place.json',     SchemaVersions::UDB3_CORE, SchemaVersions::PLACE_MAPPING_HASH,     'UDB3_CORE'],
            'organizer' => ['mapping_organizer.json', SchemaVersions::UDB3_CORE, SchemaVersions::ORGANIZER_MAPPING_HASH, 'UDB3_CORE'],
            'region'    => ['mapping_region.json',    SchemaVersions::GEOSHAPES, SchemaVersions::REGION_MAPPING_HASH,    'GEOSHAPES'],
        ];
    }

    /**
     * @test
     * @dataProvider mappingHashDataProvider
     */
    public function it_has_a_matching_hash_for_mapping(
        string $file,
        int $version,
        string $expectedHash,
        string $versionConstant
    ): void {
        $contents = file_get_contents(self::MAPPING_DIR . $file);
        $this->assertNotFalse($contents, "Could not read {$file}");
        $this->assertSame(
            $expectedHash,
            md5($contents . $version),
            "{$file} has changed. Update SchemaVersions::{$versionConstant} and run bin/generate-schema-hashes.php to get the new hash values."
        );
    }
}

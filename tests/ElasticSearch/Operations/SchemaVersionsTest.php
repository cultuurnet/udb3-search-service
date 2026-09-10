<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use PHPUnit\Framework\TestCase;

final class SchemaVersionsTest extends TestCase
{
    /**
     * @test
     */
    public function it_derives_the_udb3_core_version_from_the_mapping_files(): void
    {
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', SchemaVersions::udb3Core());
    }

    /**
     * @test
     */
    public function it_derives_the_geoshapes_version_from_the_mapping_files(): void
    {
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', SchemaVersions::geoshapes());
    }
}

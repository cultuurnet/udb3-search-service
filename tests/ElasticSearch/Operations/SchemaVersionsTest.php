<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use PHPUnit\Framework\TestCase;

final class SchemaVersionsTest extends TestCase
{
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
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class SchemaVersionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_has_the_latest_udb3_core_version()
    {
        $this->assertTrue(defined(SchemaVersions::class . '::UDB3_CORE'));
    }

    /**
     * @test
     */
    public function it_has_the_latest_geoshapes_version()
    {
        $this->assertTrue(defined(SchemaVersions::class . '::GEOSHAPES'));
    }
}

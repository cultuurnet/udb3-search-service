<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use PHPUnit\Framework\TestCase;

final class SchemaVersionsTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideSchemaVersionMethods
     */
    public function it_derives_the_version_from_the_mapping_files(string $method): void
    {
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', SchemaVersions::{$method}());
    }

    public function provideSchemaVersionMethods(): array
    {
        return [
            'udb3Core' => ['udb3Core'],
            'geoshapes' => ['geoshapes'],
        ];
    }
}

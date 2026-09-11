<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class SchemaVersions
{
    private const MAPPING_DIR = __DIR__ . '/json/';

    public static function udb3Core(): string
    {
        return md5(
            self::readMappingFile('mapping_udb3_core.json') .
            self::readMappingFile('mapping_event.json') .
            self::readMappingFile('mapping_place.json') .
            self::readMappingFile('mapping_organizer.json')
        );
    }

    public static function geoshapes(): string
    {
        return md5(self::readMappingFile('mapping_region.json'));
    }

    private static function readMappingFile(string $filename): string
    {
        $contents = file_get_contents(self::MAPPING_DIR . $filename);
        if ($contents === false) {
            throw new \RuntimeException('Could not read mapping file: ' . $filename);
        }
        return $contents;
    }
}

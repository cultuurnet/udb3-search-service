<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\Search\ElasticSearch\Operations\SchemaVersions;

$mappingDir = __DIR__ . '/../src/ElasticSearch/Operations/json/';

$mappings = [
    'UDB3_CORE_MAPPING_HASH' => ['file' => 'mapping_udb3_core.json', 'version' => SchemaVersions::UDB3_CORE],
    'EVENT_MAPPING_HASH'     => ['file' => 'mapping_event.json',     'version' => SchemaVersions::UDB3_CORE],
    'PLACE_MAPPING_HASH'     => ['file' => 'mapping_place.json',     'version' => SchemaVersions::UDB3_CORE],
    'ORGANIZER_MAPPING_HASH' => ['file' => 'mapping_organizer.json', 'version' => SchemaVersions::UDB3_CORE],
    'REGION_MAPPING_HASH'    => ['file' => 'mapping_region.json',    'version' => SchemaVersions::GEOSHAPES],
];

foreach ($mappings as $constant => $mapping) {
    $contents = file_get_contents($mappingDir . $mapping['file']);
    $hash = md5($contents . $mapping['version']);
    echo "public const {$constant} = '{$hash}';" . PHP_EOL;
}

<?php

declare(strict_types=1);

$mappingDir = __DIR__ . '/../src/ElasticSearch/Operations/json/';

$hashes = [
    'UDB3_CORE' => md5(
        file_get_contents($mappingDir . 'mapping_udb3_core.json') .
        file_get_contents($mappingDir . 'mapping_event.json') .
        file_get_contents($mappingDir . 'mapping_place.json') .
        file_get_contents($mappingDir . 'mapping_organizer.json')
    ),
    'GEOSHAPES' => md5(file_get_contents($mappingDir . 'mapping_region.json')),
];

foreach ($hashes as $constant => $hash) {
    echo "public const {$constant} = '{$hash}';" . PHP_EOL;
}

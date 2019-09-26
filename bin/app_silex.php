#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\SchemaVersions;
use CultuurNet\UDB3\SearchService\Console\CreateAutocompleteAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateIndexCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseExactMatchAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseStandardAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\DeleteIndexCommand;
use CultuurNet\UDB3\SearchService\Console\FlandersRegionTaxonomyToFacetMappingsCommand;
use CultuurNet\UDB3\SearchService\Console\IndexRegionsCommand;
use CultuurNet\UDB3\SearchService\Console\InstallGeoShapesCommand;
use CultuurNet\UDB3\SearchService\Console\InstallUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\MigrateElasticSearchCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexPermanentOffersCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\CheckIndexExistsCommand;
use CultuurNet\UDB3\SearchService\Console\TermTaxonomyToFacetMappingsCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateEventMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateIndexAliasCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateOrganizerMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdatePlaceMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateRegionMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateRegionQueryMappingCommand;
use Knp\Provider\ConsoleServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var \Silex\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$app->register(
    new ConsoleServiceProvider(),
    [
        'console.name'              => 'UDB3-Search',
        'console.version'           => '1.0.0',
        'console.project_directory' => __DIR__.'/..'
    ]
);

/** @var \Knp\Console\Application $consoleApp */
$app = $app['console'];

$app->add(
    (new ConsumeCommand('consume-udb3-core', 'amqp.udb3-core'))
        ->setDescription('Process messages from UDB3 core')
);

$app->add(new TermTaxonomyToFacetMappingsCommand());
$app->add(new FlandersRegionTaxonomyToFacetMappingsCommand());

/**
 * Elasticsearch.
 */
$app->add(new MigrateElasticSearchCommand());

/**
 * Templates.
 */
$app->add(new CreateLowerCaseExactMatchAnalyzerCommand());
$app->add(new CreateLowerCaseStandardAnalyzerCommand());
$app->add(new CreateAutocompleteAnalyzerCommand());

/**
 * Generic index commands.
 */
$app->add(new CheckIndexExistsCommand());
$app->add(new CreateIndexCommand());
$app->add(new DeleteIndexCommand());
$app->add(new UpdateIndexAliasCommand());

/**
 * Dynamic config.
 */
$app['config'] = array_merge_recursive(
    $app['config'],
    [
        'elasticsearch' => [
            'udb3_core_index' => [
                'latest' => $app['config']['elasticsearch']['udb3_core_index']['prefix'] . SchemaVersions::UDB3_CORE,
            ],
            'geoshapes_index' => [
                'latest' => $app['config']['elasticsearch']['geoshapes_index']['prefix'] . SchemaVersions::GEOSHAPES,
            ],
        ],
    ]
);

/**
 * UDB3 core.
 */
$app->add(
    new UpdateOrganizerMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['organizer']['document_type']
    )
);

$app->add(
    new UpdateEventMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['event']['document_type']
    )
);

$app->add(
    new UpdatePlaceMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['place']['document_type']
    )
);

$app->add(
    new UpdateRegionQueryMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['region_query']['document_type']
    )
);

$app->add(
    new ReindexUDB3CoreCommand(
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['from'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['scroll_ttl'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['scroll_size'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['bulk_threshold']
    )
);

$app->add(
    new ReindexPermanentOffersCommand(
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['from'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['scroll_ttl'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['scroll_size'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['bulk_threshold']
    )
);

$app->add(
    new InstallUDB3CoreCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['udb3_core_index']['write_alias'],
        $app['config']['elasticsearch']['udb3_core_index']['read_alias']
    )
);

/**
 * Geoshapes
 */
$app->add(
    new UpdateRegionMappingCommand(
        $app['config']['elasticsearch']['geoshapes_index']['latest'],
        $app['config']['elasticsearch']['region']['document_type']
    )
);

$app->add(
    new IndexRegionsCommand(
        $app['config']['elasticsearch']['geoshapes_index']['indexation']['to'],
        __DIR__ . '/../' . $app['config']['elasticsearch']['geoshapes_index']['indexation']['path'],
        $app['config']['elasticsearch']['geoshapes_index']['indexation']['fileName']
    )
);

$app->add(
    new InstallGeoShapesCommand(
        $app['config']['elasticsearch']['geoshapes_index']['latest'],
        $app['config']['elasticsearch']['geoshapes_index']['write_alias'],
        $app['config']['elasticsearch']['geoshapes_index']['read_alias']
    )
);

$app->run();

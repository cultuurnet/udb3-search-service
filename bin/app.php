#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\SearchService\Console\CreateIndexCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseExactMatchAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\DeleteIndexCommand;
use CultuurNet\UDB3\SearchService\Console\IndexGeoShapesCommand;
use CultuurNet\UDB3\SearchService\Console\InstallGeoShapesCommand;
use CultuurNet\UDB3\SearchService\Console\InstallUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\MigrateElasticSearchCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\TestIndexExistsCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateEventMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateIndexAliasCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateOrganizerMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdatePlaceMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateRegionMappingCommand;
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
$consoleApp = $app['console'];

$consoleApp->add(
    (new ConsumeCommand('consume-udb3-core', 'amqp.udb3-core'))
        ->setDescription('Process messages from UDB3 core')
);

/**
 * Elasticsearch.
 */
$consoleApp->add(new MigrateElasticSearchCommand());

/**
 * Templates.
 */
$consoleApp->add(new CreateLowerCaseExactMatchAnalyzerCommand());

/**
 * UDB3 core.
 */
$consoleApp->add(
    new TestIndexExistsCommand(
        'udb3-core:test-latest',
        'Tests whether the latest udb3_core index exists or not.',
        $app['config']['elasticsearch']['udb3_core_index']['latest']
    )
);

$consoleApp->add(
    new TestIndexExistsCommand(
        'udb3-core:test-previous',
        'Tests whether the previous udb3_core index exists or not.',
        $app['config']['elasticsearch']['udb3_core_index']['previous']
    )
);

$consoleApp->add(
    new CreateIndexCommand(
        'udb3-core:create-latest',
        'Create the latest udb3_core index.',
        $app['config']['elasticsearch']['udb3_core_index']['latest']
    )
);

$consoleApp->add(
    new UpdateIndexAliasCommand(
        'udb3-core:update-write-alias',
        'Move the write alias to the latest udb3_core index.',
        $app['config']['elasticsearch']['udb3_core_index']['write_alias'],
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['udb3_core_index']['previous']
    )
);

$consoleApp->add(
    new UpdateOrganizerMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['organizer']['document_type']
    )
);

$consoleApp->add(
    new UpdateEventMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['event']['document_type']
    )
);

$consoleApp->add(
    new UpdatePlaceMappingCommand(
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['place']['document_type']
    )
);

$consoleApp->add(
    new ReindexUDB3CoreCommand(
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['from'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['scroll_ttl'],
        $app['config']['elasticsearch']['udb3_core_index']['reindexation']['scroll_size']
    )
);

$consoleApp->add(
    new UpdateIndexAliasCommand(
        'udb3-core:update-read-alias',
        'Move the read alias to the latest udb3_core index.',
        $app['config']['elasticsearch']['udb3_core_index']['read_alias'],
        $app['config']['elasticsearch']['udb3_core_index']['latest'],
        $app['config']['elasticsearch']['udb3_core_index']['previous']
    )
);

$consoleApp->add(
    new DeleteIndexCommand(
        'udb3-core:delete-previous',
        'Delete the previous udb3_core index.',
        $app['config']['elasticsearch']['udb3_core_index']['previous']
    )
);

$consoleApp->add(new InstallUDB3CoreCommand());

/**
 * Geoshapes
 */
$consoleApp->add(
    new TestIndexExistsCommand(
        'geoshapes:test-latest',
        'Tests whether the latest geoshapes index exists or not.',
        $app['config']['elasticsearch']['geoshapes_index']['latest']
    )
);

$consoleApp->add(
    new TestIndexExistsCommand(
        'geoshapes:test-previous',
        'Tests whether the previous geoshapes index exists or not.',
        $app['config']['elasticsearch']['geoshapes_index']['previous']
    )
);

$consoleApp->add(
    new CreateIndexCommand(
        'geoshapes:create-latest',
        'Create the latest geoshapes index.',
        $app['config']['elasticsearch']['geoshapes_index']['latest']
    )
);

$consoleApp->add(
    new UpdateIndexAliasCommand(
        'geoshapes:update-write-alias',
        'Move the write alias to the latest geoshapes index.',
        $app['config']['elasticsearch']['geoshapes_index']['write_alias'],
        $app['config']['elasticsearch']['geoshapes_index']['latest'],
        $app['config']['elasticsearch']['geoshapes_index']['previous']
    )
);

$consoleApp->add(
    new UpdateRegionMappingCommand(
        $app['config']['elasticsearch']['geoshapes_index']['latest'],
        $app['config']['elasticsearch']['region']['document_type']
    )
);

$consoleApp->add(
    new IndexGeoShapesCommand(
        $app['config']['elasticsearch']['geoshapes_index']['indexation']['to'],
        __DIR__ . '/../' . $app['config']['elasticsearch']['geoshapes_index']['indexation']['path'],
        $app['config']['elasticsearch']['geoshapes_index']['indexation']['fileName']
    )
);

$consoleApp->add(
    new UpdateIndexAliasCommand(
        'geoshapes:update-read-alias',
        'Move the read alias to the latest geoshapes index.',
        $app['config']['elasticsearch']['geoshapes_index']['read_alias'],
        $app['config']['elasticsearch']['geoshapes_index']['latest'],
        $app['config']['elasticsearch']['geoshapes_index']['previous']
    )
);

$consoleApp->add(
    new DeleteIndexCommand(
        'geoshapes:delete-previous',
        'Delete the previous geoshapes index.',
        $app['config']['elasticsearch']['geoshapes_index']['previous']
    )
);

$consoleApp->add(new InstallGeoShapesCommand());

$consoleApp->run();

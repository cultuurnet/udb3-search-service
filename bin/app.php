#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use CultuurNet\UDB3\SearchService\Console\CreateIndexCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\DeleteIndexCommand;
use CultuurNet\UDB3\SearchService\Console\InstallUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\TestIndexExistsCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateEventMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateIndexAliasCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateOrganizerMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdatePlaceMappingCommand;
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
 * Templates.
 */
$consoleApp->add(new CreateLowerCaseAnalyzerCommand());

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

$consoleApp->run();

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\SearchService\Offer\OfferControllerProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerControllerProvider;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use ValueObjects\StringLiteral\StringLiteral;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

/**
 * Allow to use services as controllers.
 */
$app->register(new ServiceControllerServiceProvider());

/**
 * Return exceptions as APIProblem responses.
 */
$app->register(
    new \CultuurNet\UDB3\SearchService\Error\HttpErrorHandlerProvider(),
    [
        'api_problem.stacktrace' => (bool) $app['config']['debug'],
    ]
);

$app->mount('organizers', new OrganizerControllerProvider());

$app->mount(
    'offers',
    new OfferControllerProvider(
        new StringLiteral($app['config']['elasticsearch']['region']['read_index']),
        new StringLiteral($app['config']['elasticsearch']['region']['document_type'])
    )
);

$app->after($app['cors']);

$app->run();

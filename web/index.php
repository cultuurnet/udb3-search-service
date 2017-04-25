<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\SearchService\Event\EventControllerProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferControllerProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerControllerProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceControllerProvider;
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
 * In debug mode the standard Silex error page is shown with stack trace.
 */
if (!$app['config']['debug']) {
    $app->register(
        new \CultuurNet\UDB3\SearchService\Error\HttpErrorHandlerProvider()
    );
}

$app->mount('organizers', new OrganizerControllerProvider());

$app->mount(
    'offers',
    new OfferControllerProvider(
        new StringLiteral($app['config']['elasticsearch']['region']['read_index']),
        new StringLiteral($app['config']['elasticsearch']['region']['document_type'])
    )
);

$app->mount(
    'events',
    new EventControllerProvider(
        new StringLiteral($app['config']['elasticsearch']['region']['read_index']),
        new StringLiteral($app['config']['elasticsearch']['region']['document_type'])
    )
);

$app->mount(
    'places',
    new PlaceControllerProvider(
        new StringLiteral($app['config']['elasticsearch']['region']['read_index']),
        new StringLiteral($app['config']['elasticsearch']['region']['document_type'])
    )
);

$app->after($app['cors']);

$app->run();

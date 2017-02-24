<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\SearchService\Offer\OfferControllerProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerControllerProvider;
use CultuurNet\UDB3\SearchService\Region\RegionControllerProvider;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use ValueObjects\StringLiteral\StringLiteral;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

/**
 * Allow to use services as controllers.
 */
$app->register(new ServiceControllerServiceProvider());

$app->mount('organizers', new OrganizerControllerProvider());

$app->mount(
    'offers',
    new OfferControllerProvider(
        new StringLiteral($app['config']['elasticsearch']['region']['index_name']),
        new StringLiteral($app['config']['elasticsearch']['region']['document_type'])
    )
);

$app->mount('regions', new RegionControllerProvider());

$app->after($app['cors']);

$app->run();

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\Search\Http\Authentication\ApiKey\ApiKey;
use CultuurNet\UDB3\SearchService\Authentication\AuthenticationServiceProvider;
use CultuurNet\UDB3\SearchService\Event\EventControllerProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferControllerProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerControllerProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceControllerProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

/**
 * API key authentication.
 */
$app['request_logger'] = $app->share(
    function () {
        $logger = new Monolog\Logger('request_logger');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $logFileHandler = new StreamHandler(
            __DIR__ . '/../log/requests.log',
            Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);

        return $logger;
    }
);

$app->register(new AuthenticationServiceProvider());

$app->before(
    function (Request $request, Application $app) {
        $app['auth.request_authenticator']->authenticate($request);
        $app['request_time'] = microtime(true);
    }
);

$app->after(
    function (Request $request, Response $response, Application $app) {
        $requestTime = $app['request_time'];
        $responseTime = microtime(true);
        $duration = $responseTime - $requestTime;
        $apiKey = $app['auth.api_key_reader']->read($request);

        if ($apiKey instanceof ApiKey) {
            $apiKey = $apiKey->toNative();
        }

        /* @var Logger $logger */
        $logger = $app['request_logger'];
        $logger->info(
            $request->__toString(),
            [
                'api_key' => $apiKey,
                'request_time' => $requestTime,
                'response_time' => $responseTime,
                'duration' => $duration,
            ]
        );
    }
);

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

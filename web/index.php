<?php

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use CultuurNet\UDB3\SearchService\Factory\ErrorHandlerFactory;
use League\Route\Router;
use Sentry\State\HubInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

$config = ConfigFactory::create(__DIR__ . '/../');

$container = ContainerFactory::forWeb($config);
$errorHandler = ErrorHandlerFactory::forWeb($container->get(HubInterface::class), $config->get('debug'));
$errorHandler->register();

$response = $container->get(Router::class)->dispatch(
    new ApiRequest(
        ServerRequestFactory::createFromGlobals()
    )
);
(new SapiStreamEmitter())->emit($response);

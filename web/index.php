<?php

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use League\Route\Router;
use Slim\Psr7\Factory\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $container = ContainerFactory::forWeb();

    $response = $container->get(Router::class)->dispatch(
        new ApiRequest(
            ServerRequestFactory::createFromGlobals()
        )
    );
    (new SapiStreamEmitter())->emit($response);
} catch (Throwable $throwable) {
    // @todo: this is temporary, remove when not needed anymore
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    var_dump($throwable);
    echo $throwable->getTraceAsString();
}

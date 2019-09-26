<?php

use CultuurNet\UDB3\Search\Http\ApiRequest;
use League\Container\Container;
use League\Route\Router;
use Slim\Psr7\Factory\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    
    /** @var Container $container */
    $container = require __DIR__ . '/../container.php';
    
    $response = $container->get(Router::class)->dispatch(
        new ApiRequest(
            ServerRequestFactory::createFromGlobals()
        )
    );
    
    (new SapiStreamEmitter())->emit($response);
    
} catch (Throwable $throwable) {
    // @todo: this is temporary, remove when not needed anymore
    var_dump($throwable);
    echo $throwable->getTraceAsString();
}



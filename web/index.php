<?php

use CultuurNet\UDB3\SearchService\Organizer\LeagueOrganizerServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Router;
use Slim\Psr7\Factory\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    
    $container->add(
        Config::class,
        function () {
            return Config::load(__DIR__ . '/../config.yml', new Yaml());
        }
    );

    $container->addServiceProvider(\CultuurNet\UDB3\SearchService\RoutingServiceProvider::class);
    $container->addServiceProvider(LeagueOrganizerServiceProvider::class);
    
    
    $response = $container->get(Router::class)->dispatch(
        ServerRequestFactory::createFromGlobals()
    );
    
    (new SapiStreamEmitter())->emit($response);
    
} catch (Throwable $throwable) {
    // @todo: this is temporary, remove when not needed anymore
    var_dump($throwable);
    echo $throwable->getTraceAsString();
}



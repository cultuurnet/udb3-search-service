<?php

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\SearchService\LeagueElasticSearchProvider;
use CultuurNet\UDB3\SearchService\Offer\LeagueOfferProvider;
use CultuurNet\UDB3\SearchService\Organizer\LeagueOrganizerServiceProvider;
use CultuurNet\UDB3\SearchService\RoutingServiceProvider;
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
            $configFiles = [
                __DIR__ . '/../config.yml',
                __DIR__ . '/../facet_mapping_facilities.yml',
                __DIR__ . '/../facet_mapping_regions.yml',
                __DIR__ . '/../facet_mapping_themes.yml',
                __DIR__ . '/../facet_mapping_types.yml',
                __DIR__ . '/../features.yml',
            ];
            return Config::load($configFiles, new Yaml());
        }
    );
    
    $container->addServiceProvider(RoutingServiceProvider::class);
    $container->addServiceProvider(LeagueOrganizerServiceProvider::class);
    $container->addServiceProvider(LeagueOfferProvider::class);
    $container->addServiceProvider(LeagueElasticSearchProvider::class);
    
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



<?php

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\SearchService\ApiKey\ApiGuardServiceProvider;
use CultuurNet\UDB3\SearchService\CommandServiceProvider;
use CultuurNet\UDB3\SearchService\ElasticSearchProvider;
use CultuurNet\UDB3\SearchService\Event\EventServiceProvider;
use CultuurNet\UDB3\SearchService\EventBusProvider;
use CultuurNet\UDB3\SearchService\HttpClientProvider;
use CultuurNet\UDB3\SearchService\LoggerProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferServiceProvider;
use CultuurNet\UDB3\SearchService\RoutingServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

class ContainerFactory
{
    public static function forCli(): Container
    {
        $container = self::build();
        $container->addServiceProvider(CommandServiceProvider::class);
        return $container;
    }
    
    public static function forWeb(): Container
    {
        $container = self::build();
        $container->addServiceProvider(RoutingServiceProvider::class);
        return $container;
    }
    
    private static function build(): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $container->add(
            Config::class,
            function () {
                $configFiles = [
                    __DIR__ . '/../../config.yml',
                    __DIR__ . '/../../facet_mapping_facilities.yml',
                    __DIR__ . '/../../facet_mapping_regions.yml',
                    __DIR__ . '/../../facet_mapping_themes.yml',
                    __DIR__ . '/../../facet_mapping_types.yml',
                    __DIR__ . '/../../features.yml',
                ];
                return Config::load($configFiles, new Yaml());
            }
        );

        $container->addServiceProvider(ApiGuardServiceProvider::class);
        $container->addServiceProvider(OrganizerServiceProvider::class);
        $container->addServiceProvider(OfferServiceProvider::class);
        $container->addServiceProvider(ElasticSearchProvider::class);
        $container->addServiceProvider(EventServiceProvider::class);
        $container->addServiceProvider(PlaceServiceProvider::class);
        $container->addServiceProvider(EventBusProvider::class);
        $container->addServiceProvider(LoggerProvider::class);
        $container->addServiceProvider(HttpClientProvider::class);
        
        return $container;
    }
}

<?php

use CultuurNet\UDB3\SearchService\LeagueElasticSearchProvider;
use CultuurNet\UDB3\SearchService\Offer\LeagueOfferProvider;
use CultuurNet\UDB3\SearchService\Organizer\LeagueOrganizerServiceProvider;
use CultuurNet\UDB3\SearchService\RoutingServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

$container = new Container();
$container->delegate(new ReflectionContainer());
$container->add(
    Config::class,
    function () {
        $configFiles = [
            __DIR__ . '/config.yml',
            __DIR__ . '/facet_mapping_facilities.yml',
            __DIR__ . '/facet_mapping_regions.yml',
            __DIR__ . '/facet_mapping_themes.yml',
            __DIR__ . '/facet_mapping_types.yml',
            __DIR__ . '/features.yml',
        ];
        return Config::load($configFiles, new Yaml());
    }
);

$container->addServiceProvider(RoutingServiceProvider::class);
$container->addServiceProvider(\CultuurNet\UDB3\SearchService\CommandServiceProvider::class);
$container->addServiceProvider(LeagueOrganizerServiceProvider::class);
$container->addServiceProvider(LeagueOfferProvider::class);
$container->addServiceProvider(LeagueElasticSearchProvider::class);

return $container;
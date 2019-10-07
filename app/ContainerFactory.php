<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\SearchService\Offer\OfferProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

class ContainerFactory
{
    public static function build(): Container
    {
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

        $container->addServiceProvider(OrganizerServiceProvider::class);
        $container->addServiceProvider(OfferProvider::class);
        $container->addServiceProvider(ElasticSearchProvider::class);

        return $container;
    }
}

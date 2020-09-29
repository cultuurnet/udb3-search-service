<?php

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\SearchService\AmqpProvider;
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
use CultuurNet\UDB3\SearchService\SentryServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Noodlehaus\Config;

class ContainerFactory
{
    public static function forCli(Config $config): Container
    {
        $container = self::build($config);
        $container->addServiceProvider(CommandServiceProvider::class);
        return $container;
    }

    public static function forWeb(Config $config): Container
    {
        $container = self::build($config);
        $container->addServiceProvider(RoutingServiceProvider::class);
        return $container;
    }

    private static function build(Config $config): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $container->add(
            Config::class,
            $config
        );

        $container->addServiceProvider(SentryServiceProvider::class);
        $container->addServiceProvider(ApiGuardServiceProvider::class);
        $container->addServiceProvider(OrganizerServiceProvider::class);
        $container->addServiceProvider(OfferServiceProvider::class);
        $container->addServiceProvider(ElasticSearchProvider::class);
        $container->addServiceProvider(EventServiceProvider::class);
        $container->addServiceProvider(PlaceServiceProvider::class);
        $container->addServiceProvider(EventBusProvider::class);
        $container->addServiceProvider(LoggerProvider::class);
        $container->addServiceProvider(HttpClientProvider::class);
        $container->addServiceProvider(AmqpProvider::class);

        return $container;
    }
}

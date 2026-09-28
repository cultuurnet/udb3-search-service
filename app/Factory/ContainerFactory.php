<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\SearchService\AmqpProvider;
use CultuurNet\UDB3\SearchService\CacheProvider;
use CultuurNet\UDB3\SearchService\CommandServiceProvider;
use CultuurNet\UDB3\SearchService\ElasticSearchProvider;
use CultuurNet\UDB3\SearchService\Event\EventIndexationServiceProvider;
use CultuurNet\UDB3\SearchService\Event\EventSearchServiceProvider;
use CultuurNet\UDB3\SearchService\EventBusProvider;
use CultuurNet\UDB3\SearchService\JsonDocumentFetcherProvider;
use CultuurNet\UDB3\SearchService\AmqpLoggerProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerIndexationServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceIndexationServiceProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceSearchServiceProvider;
use CultuurNet\UDB3\SearchService\RoutingServiceProvider;
use CultuurNet\UDB3\SearchService\Error\SentryCliServiceProvider;
use CultuurNet\UDB3\SearchService\Error\SentryHubServiceProvider;
use CultuurNet\UDB3\SearchService\Error\SentryWebServiceProvider;
use CultuurNet\UDB3\SearchService\Taxonomy\TaxonomyServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Noodlehaus\Config;

final class ContainerFactory
{
    public static function forCli(Config $config): Container
    {
        $container = self::build($config);
        $container->addServiceProvider(new SentryCliServiceProvider());
        $container->addServiceProvider(new AmqpLoggerProvider());
        $container->addServiceProvider(new AmqpProvider());
        $container->addServiceProvider(new EventBusProvider());
        $container->addServiceProvider(new JsonDocumentFetcherProvider());
        $container->addServiceProvider(new OrganizerIndexationServiceProvider());
        $container->addServiceProvider(new EventIndexationServiceProvider());
        $container->addServiceProvider(new PlaceIndexationServiceProvider());
        $container->addServiceProvider(new CommandServiceProvider());
        $container->addServiceProvider(new CacheProvider());
        return $container;
    }

    public static function forWeb(Config $config): Container
    {
        $container = self::build($config);
        $container->addServiceProvider(new SentryWebServiceProvider());
        $container->addServiceProvider(new OrganizerSearchServiceProvider());
        $container->addServiceProvider(new OfferSearchServiceProvider());
        $container->addServiceProvider(new EventSearchServiceProvider());
        $container->addServiceProvider(new PlaceSearchServiceProvider());
        $container->addServiceProvider(new RoutingServiceProvider());
        $container->addServiceProvider(new TaxonomyServiceProvider());
        $container->addServiceProvider(new CacheProvider());
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

        $container->addServiceProvider(new SentryHubServiceProvider());
        $container->addServiceProvider(new ElasticSearchProvider());

        return $container;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\SearchService\AmqpProvider;
use CultuurNet\UDB3\SearchService\ApiKey\ApiGuardServiceProvider;
use CultuurNet\UDB3\SearchService\CommandServiceProvider;
use CultuurNet\UDB3\SearchService\ElasticSearchProvider;
use CultuurNet\UDB3\SearchService\Event\EventIndexationServiceProvider;
use CultuurNet\UDB3\SearchService\Event\EventSearchServiceProvider;
use CultuurNet\UDB3\SearchService\EventBusProvider;
use CultuurNet\UDB3\SearchService\HttpClientProvider;
use CultuurNet\UDB3\SearchService\JsonDocumentFetcherProvider;
use CultuurNet\UDB3\SearchService\AmqpLoggerProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerIndexationServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceIndexationServiceProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceSearchServiceProvider;
use CultuurNet\UDB3\SearchService\RoutingServiceProvider;
use CultuurNet\UDB3\SearchService\Error\SentryCliServiceProvider;
use CultuurNet\UDB3\SearchService\Error\SentryHubServiceProvider;
use CultuurNet\UDB3\SearchService\Error\SentryWebServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Noodlehaus\Config;

final class ContainerFactory
{
    public static function forCli(Config $config): Container
    {
        $container = self::build($config);
        $container->addServiceProvider(SentryCliServiceProvider::class);
        $container->addServiceProvider(CommandServiceProvider::class);
        return $container;
    }

    public static function forWeb(Config $config): Container
    {
        $container = self::build($config);
        $container->addServiceProvider(SentryWebServiceProvider::class);
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

        $container->addServiceProvider(SentryHubServiceProvider::class);
        $container->addServiceProvider(ApiGuardServiceProvider::class);
        $container->addServiceProvider(JsonDocumentFetcherProvider::class);
        $container->addServiceProvider(OrganizerServiceProvider::class);
        $container->addServiceProvider(OrganizerIndexationServiceProvider::class);
        $container->addServiceProvider(OfferServiceProvider::class);
        $container->addServiceProvider(ElasticSearchProvider::class);
        $container->addServiceProvider(EventSearchServiceProvider::class);
        $container->addServiceProvider(EventIndexationServiceProvider::class);
        $container->addServiceProvider(PlaceSearchServiceProvider::class);
        $container->addServiceProvider(PlaceIndexationServiceProvider::class);
        $container->addServiceProvider(EventBusProvider::class);
        $container->addServiceProvider(AmqpLoggerProvider::class);
        $container->addServiceProvider(HttpClientProvider::class);
        $container->addServiceProvider(AmqpProvider::class);

        return $container;
    }
}

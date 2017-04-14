<?php

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\BroadwayAMQP\DomainMessageJSONDeserializer;
use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\SearchService\ElasticSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Event\EventElasticSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Event\EventServiceProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferElasticSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerElasticSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceElasticSearchServiceProvider;
use CultuurNet\UDB3\SearchService\Place\PlaceServiceProvider;
use CultuurNet\UDB3\SimpleEventBus;
use DerAlex\Silex\YamlConfigServiceProvider;
use GuzzleHttp\Client;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

$app = new Application();

if (!isset($appConfigLocation)) {
    $appConfigLocation =  __DIR__;
}
$app->register(new YamlConfigServiceProvider($appConfigLocation . '/config.yml'));

$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => implode(" ", $app['config']['cors']['origins']),
    "cors.allowCredentials" => false
));

/**
 * Turn debug on or off.
 */
$app['debug'] = $app['config']['debug'] === true;

/**
 * Load additional bootstrap files.
 */
foreach ($app['config']['bootstrap'] as $identifier => $enabled) {
    if (true === $enabled) {
        require __DIR__ . "/bootstrap/{$identifier}.php";
    }
}

$app['http_client'] = $app->share(
    function () {
        return new Client();
    }
);

$app['file_finder'] = $app->share(
    function () {
        return new Finder();
    }
);

$app['event_bus.udb3-core'] = $app->share(
    function (Application $app) {
        $bus =  new SimpleEventBus();

        $bus->beforeFirstPublication(function (EventBusInterface $eventBus) use ($app) {
            $subscribers = [
                'organizer_search_projector',
                'event_search_projector',
                'place_search_projector',
            ];

            // Allow to override event bus subscribers through configuration.
            if (isset($app['config']['event_bus']) &&
                isset($app['config']['event_bus']['subscribers'])) {

                $subscribers = $app['config']['event_bus']['subscribers'];
            }

            foreach ($subscribers as $subscriberServiceId) {
                $eventBus->subscribe($app[$subscriberServiceId]);
            }
        });

        return $bus;
    }
);

$app['logger.amqp.udb3_consumer'] = $app->share(
    function () {
        $logger = new Monolog\Logger('amqp.udb3_publisher');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $logFileHandler = new StreamHandler(
            __DIR__ . '/log/amqp.log',
            Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);

        return $logger;
    }
);

$app->register(
    new \CultuurNet\SilexAMQP\AMQPConnectionServiceProvider(),
    [
        'amqp.connection.host' => $app['config']['amqp']['host'],
        'amqp.connection.port' => $app['config']['amqp']['port'],
        'amqp.connection.user' => $app['config']['amqp']['user'],
        'amqp.connection.password' => $app['config']['amqp']['password'],
        'amqp.connection.vhost' => $app['config']['amqp']['vhost'],
    ]
);

$app['deserializer_locator'] = $app->share(
    function () {
        $deserializerLocator = new SimpleDeserializerLocator();
        $maps =
            \CultuurNet\UDB3\Event\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Place\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Label\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Organizer\Events\ContentTypes::map();

        foreach ($maps as $payloadClass => $contentType) {
            $deserializerLocator->registerDeserializer(
                new StringLiteral($contentType),
                new DomainMessageJSONDeserializer($payloadClass)
            );
        }
        return $deserializerLocator;
    }
);

$app['event_bus_forwarding_consumer_factory'] = $app->share(
    function (Application $app) {
        return new EventBusForwardingConsumerFactory(
            Natural::fromNative($app['config']['consumerExecutionDelay']),
            $app['amqp.connection'],
            $app['logger.amqp.udb3_consumer'],
            $app['deserializer_locator'],
            $app['event_bus.udb3-core'],
            new StringLiteral($app['config']['amqp']['consumer_tag'])
        );
    }
);

foreach ($app['config']['amqp']['consumers'] as $consumerId => $consumerConfig) {
    $app['amqp.' . $consumerId] = $app->share(
        function (Application $app) use ($consumerId, $consumerConfig) {
            $exchange = new StringLiteral($consumerConfig['exchange']);
            $queue = new StringLiteral($consumerConfig['queue']);

            /** @var EventBusForwardingConsumerFactory $consumerFactory */
            $consumerFactory = $app['event_bus_forwarding_consumer_factory'];

            return $consumerFactory->create($exchange, $queue);
        }
    );
}

$app->register(
    new ElasticSearchServiceProvider(),
    [
        'elasticsearch.host' => $app['config']['elasticsearch']['host'],
    ]
);

/**
 * Organizers.
 */
$app->register(
    new OrganizerElasticSearchServiceProvider(),
    [
        'elasticsearch.organizer.read_index' => $app['config']['elasticsearch']['organizer']['read_index'],
        'elasticsearch.organizer.write_index' => $app['config']['elasticsearch']['organizer']['write_index'],
        'elasticsearch.organizer.document_type' => $app['config']['elasticsearch']['organizer']['document_type'],
    ]
);

$app->register(new OrganizerServiceProvider());

/**
 * Events.
 */
$app->register(
    new EventElasticSearchServiceProvider(),
    [
        'elasticsearch.event.read_index' => $app['config']['elasticsearch']['event']['read_index'],
        'elasticsearch.event.write_index' => $app['config']['elasticsearch']['event']['write_index'],
        'elasticsearch.event.document_type' => $app['config']['elasticsearch']['event']['document_type'],
    ]
);

$app->register(new EventServiceProvider());

/**
 * Places.
 */
$app->register(
    new PlaceElasticSearchServiceProvider(),
    [
        'elasticsearch.place.read_index' => $app['config']['elasticsearch']['place']['read_index'],
        'elasticsearch.place.write_index' => $app['config']['elasticsearch']['place']['write_index'],
        'elasticsearch.place.document_type' => $app['config']['elasticsearch']['place']['document_type'],
    ]
);

$app->register(new PlaceServiceProvider());

/**
 * Offers.
 */
$app->register(
    new OfferElasticSearchServiceProvider(),
    [
        'elasticsearch.offer.read_index' => $app['config']['elasticsearch']['offer']['read_index'],
        'elasticsearch.offer.write_index' => $app['config']['elasticsearch']['offer']['write_index'],
        'elasticsearch.offer.document_type' => $app['config']['elasticsearch']['offer']['document_type'],
        'elasticsearch.facet_mapping.regions' => $app['config']['facet_mapping_regions'],
    ]
);

return $app;

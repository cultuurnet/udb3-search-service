<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\AMQP\DomainMessageJSONDeserializer;
use CultuurNet\UDB3\Search\AMQP\EventBusForwardingConsumer;
use CultuurNet\UDB3\Search\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Search\Event\EventProjectedToJSONLD;
use CultuurNet\UDB3\Search\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class AmqpProvider extends BaseServiceProvider
{
    public function provides(string $alias): bool
    {
        foreach ($this->consumers() as $consumerId => $consumerConfig) {
            if ($alias === $this->consumerName($consumerId)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->consumers() as $consumerId => $consumerConfig) {
            $this->add(
                $this->consumerName($consumerId),
                function () use ($consumerConfig) {
                    $eventBusForwardingConsumer = new EventBusForwardingConsumer(
                        $this->get('amqp.connection'),
                        $this->get(EventBus::class),
                        $this->get('deserializer_locator'),
                        $this->parameter('amqp.consumer_tag'),
                        $consumerConfig['exchange'],
                        $consumerConfig['queue']
                    );

                    $eventBusForwardingConsumer->setLogger($this->get('logger.amqp.udb3'));

                    return $eventBusForwardingConsumer;
                }
            );
        }

        $this->add(
            'amqp.connection',
            function () {
                $connection = new AMQPStreamConnection(
                    $this->parameter('amqp.host'),
                    $this->parameter('amqp.port'),
                    $this->parameter('amqp.user'),
                    $this->parameter('amqp.password'),
                    $this->parameter('amqp.vhost')
                );

                return $connection;
            }
        );

        $this->add(
            'deserializer_locator',
            function () {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerMapping = [
                    EventProjectedToJSONLD::class =>
                        'application/vnd.cultuurnet.udb3-events.event-projected-to-jsonld+json',
                    PlaceProjectedToJSONLD::class =>
                        'application/vnd.cultuurnet.udb3-events.place-projected-to-jsonld+json',
                    OrganizerProjectedToJSONLD::class =>
                        'application/vnd.cultuurnet.udb3-events.organizer-projected-to-jsonld+json',
                ];

                foreach ($deserializerMapping as $payloadClass => $contentType) {
                    $deserializerLocator->registerDeserializer(
                        $contentType,
                        new DomainMessageJSONDeserializer($payloadClass)
                    );
                }
                return $deserializerLocator;
            }
        );
    }

    private function consumerName(string $consumerId): string
    {
        return 'amqp.' . $consumerId;
    }

    public function consumers(): array
    {
        $value = $this->parameter('amqp.consumers');
        return is_array($value) ? $value : [];
    }
}

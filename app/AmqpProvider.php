<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\AMQP\DomainMessageJSONDeserializer;
use CultuurNet\UDB3\Search\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Search\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Search\Event\EventProjectedToJSONLD;
use CultuurNet\UDB3\Search\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class AmqpProvider extends BaseServiceProvider
{

    public function provides(string $alias): bool
    {
        foreach ($this->consumers() as $consumerId => $consumerConfig) {
            if ($alias === (string) $this->consumerName($consumerId)) {
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
                    $exchange = new StringLiteral($consumerConfig['exchange']);
                    $queue = new StringLiteral($consumerConfig['queue']);

                    /** @var EventBusForwardingConsumerFactory $consumerFactory */
                    $consumerFactory = $this->get('event_bus_forwarding_consumer_factory');

                    return $consumerFactory->create($exchange, $queue);
                }
            );
        }

        $this->add(
            'event_bus_forwarding_consumer_factory',
            function () {
                return new EventBusForwardingConsumerFactory(
                    new Natural(0),
                    $this->get('amqp.connection'),
                    $this->get('logger.amqp.udb3_consumer'),
                    $this->get('deserializer_locator'),
                    $this->get(EventBusInterface::class),
                    new StringLiteral($this->parameter('amqp.consumer_tag'))
                );
            }
        );

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
                        new StringLiteral($contentType),
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

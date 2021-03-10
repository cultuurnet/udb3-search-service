<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Rhumsaa\Uuid\Uuid;

/**
 * Forwards messages coming in via AMQP to an event bus.
 */
final class EventBusForwardingConsumer extends AbstractConsumer
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @param int $delay
     */
    public function __construct(
        AMQPStreamConnection $connection,
        EventBusInterface $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        string $consumerTag,
        string $exchangeName,
        string $queueName,
        $delay = 0
    ) {
        $this->eventBus = $eventBus;

        parent::__construct(
            $connection,
            $deserializerLocator,
            $consumerTag,
            $exchangeName,
            $queueName,
            $delay,
            'event bus'
        );
    }


    protected function handle($deserializedMessage, array $context)
    {
        // If the deserializer did not return a DomainMessage yet, then
        // consider the returned value as the payload, and wrap it in a
        // DomainMessage.
        if (!$deserializedMessage instanceof DomainMessage) {
            $deserializedMessage = new DomainMessage(
                Uuid::uuid4(),
                0,
                new Metadata($context),
                $deserializedMessage,
                DateTime::now()
            );
        }

        $this->eventBus->publish(
            new DomainEventStream([$deserializedMessage])
        );
    }
}

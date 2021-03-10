<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Search\Natural;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

final class EventBusForwardingConsumerFactory
{
    /**
     * Delay the consumption of UDB2 updates with some seconds to prevent a
     * race condition with the UDB3 worker. Modifications initiated by
     * commands in the UDB3 queue worker need to finish before their
     * counterpart UDB2 update is processed.
     *
     * @var Natural
     */
    private $executionDelay;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeserializerLocatorInterface
     */
    private $deserializerLocator;

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var string
     */
    private $consumerTag;

    public function __construct(
        Natural $executionDelay,
        AMQPStreamConnection $connection,
        LoggerInterface $logger,
        DeserializerLocatorInterface $deserializerLocator,
        EventBusInterface $eventBus,
        string $consumerTag
    ) {
        $this->executionDelay = $executionDelay;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->deserializerLocator = $deserializerLocator;
        $this->eventBus = $eventBus;
        $this->consumerTag = $consumerTag;
    }

    /**
     * @return EventBusForwardingConsumer
     */
    public function create(
        string $exchange,
        string $queue
    ) {
        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $exchange,
            $queue,
            $this->executionDelay->toNative()
        );

        $eventBusForwardingConsumer->setLogger($this->logger);

        return $eventBusForwardingConsumer;
    }
}

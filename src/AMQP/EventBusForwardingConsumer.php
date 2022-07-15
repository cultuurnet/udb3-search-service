<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerNotFoundException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Throwable;

final class EventBusForwardingConsumer implements ConsumerInterface
{
    use LoggerAwareTrait;

    private array $context;
    private DeserializerLocatorInterface $deserializerLocator;
    private AMQPChannel $channel;
    private int $delay;
    private EventBus $eventBus;

    public function __construct(
        AMQPStreamConnection $connection,
        EventBus $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        string $consumerTag,
        string $exchangeName,
        string $queueName,
        int $delay = 0
    ) {
        $this->context = [];
        $this->logger = new NullLogger();
        $this->eventBus = $eventBus;

        $this->channel = $connection->channel();
        $this->channel->basic_qos(0, 4, true);

        $this->deserializerLocator = $deserializerLocator;
        $this->delay = $delay;

        $this->channel->queue_declare(
            $queueName,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $autoDelete = false
        );

        $this->channel->queue_bind(
            $queueName,
            $exchangeName,
            $routingKey = '#'
        );

        $this->channel->basic_consume(
            $queueName,
            $consumerTag,
            $noLocal = false,
            $noAck = false,
            $exclusive = false,
            $noWait = false,
            [$this, 'consume']
        );
    }

    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }

    public function consume(AMQPMessage $message): void
    {
        $this->context = [];

        if ($message->has('correlation_id')) {
            $this->context['correlation_id'] = $message->get('correlation_id');
        }

        try {
            $this->handle($message);
            $this->ack($message, 'message acknowledged');
        } catch (DeserializerNotFoundException $e) {
            $this->ack($message, 'auto acknowledged message because no deserializer was configured for it');
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), $this->context + ['exception' => $e]);
            $this->reject($message, 'message rejected');
        }
    }

    private function handle(AMQPMessage $message): void
    {
        $this->logger->info('received message with content-type ' . $message->get('content_type'), $this->context);

        $deserializer = $this->deserializerLocator->getDeserializerForContentType($message->get('content_type'));
        $deserializedMessage = $deserializer->deserialize($message->body);

        if ($this->delay > 0) {
            sleep($this->delay);
        }

        $this->logger->info('passing on message to event bus', $this->context);

        // If the deserializer did not return a DomainMessage yet, then
        // consider the returned value as the payload, and wrap it in a
        // DomainMessage.
        if (!$deserializedMessage instanceof DomainMessage) {
            $deserializedMessage = new DomainMessage(
                Uuid::uuid4(),
                0,
                new Metadata($this->context),
                $deserializedMessage,
                DateTime::now()
            );
        }

        $this->eventBus->publish(new DomainEventStream([$deserializedMessage]));
    }

    private function ack(AMQPMessage $message, string $logMessage): void
    {
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        $this->logger->info($logMessage, $this->context);
    }

    private function reject(AMQPMessage $message, string $logMessage): void
    {
        $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], false);
        $this->logger->info($logMessage, $this->context);
    }
}

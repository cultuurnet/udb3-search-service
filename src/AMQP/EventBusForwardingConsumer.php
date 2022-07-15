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
use Ramsey\Uuid\Uuid;

final class EventBusForwardingConsumer implements ConsumerInterface
{
    use LoggerAwareTrait;

    private AMQPStreamConnection $connection;
    private DeserializerLocatorInterface $deserializerLocator;
    private string $queueName;
    private string $exchangeName;
    private string $consumerTag;
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
        $this->eventBus = $eventBus;

        $this->connection = $connection;
        $this->channel = $connection->channel();
        $this->channel->basic_qos(0, 4, true);

        $this->deserializerLocator = $deserializerLocator;
        $this->queueName = $queueName;
        $this->consumerTag = $consumerTag;
        $this->exchangeName = $exchangeName;
        $this->delay = $delay;

        $this->declareQueue();
        $this->registerConsumeCallback();
    }

    protected function handle($deserializedMessage, array $context): void
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

    private function delayIfNecessary(): void
    {
        if ($this->delay > 0) {
            sleep($this->delay);
        }
    }

    public function consume(AMQPMessage $message): void
    {
        $context = [];

        if ($message->has('correlation_id')) {
            $context['correlation_id'] = $message->get('correlation_id');
        }

        try {
            if ($this->logger) {
                $this->logger->info(
                    'received message with content-type ' . $message->get(
                        'content_type'
                    ),
                    $context
                );
            }

            $deserializer = $this->deserializerLocator->getDeserializerForContentType(
                $message->get('content_type')
            );

            $deserializedMessage = $deserializer->deserialize($message->body);

            $this->delayIfNecessary();

            if ($this->logger) {
                $this->logger->info(
                    'passing on message to event bus',
                    $context
                );
            }

            $this->handle($deserializedMessage, $context);
        } catch (DeserializerNotFoundException $e) {
            $message->delivery_info['channel']->basic_ack(
                $message->delivery_info['delivery_tag']
            );
            if ($this->logger) {
                $this->logger->info(
                    'auto acknowledged message because no deserializer was configured for it',
                    $context
                );
            }

            return;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error(
                    $e->getMessage(),
                    $context + ['exception' => $e]
                );
            }

            $message->delivery_info['channel']->basic_reject(
                $message->delivery_info['delivery_tag'],
                false
            );

            if ($this->logger) {
                $this->logger->info(
                    'message rejected',
                    $context
                );
            }

            return;
        }

        $message->delivery_info['channel']->basic_ack(
            $message->delivery_info['delivery_tag']
        );

        if ($this->logger) {
            $this->logger->info(
                'message acknowledged',
                $context
            );
        }
    }

    private function declareQueue(): void
    {
        $this->channel->queue_declare(
            $this->queueName,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $autoDelete = false
        );

        $this->channel->queue_bind(
            $this->queueName,
            $this->exchangeName,
            $routingKey = '#'
        );
    }

    private function registerConsumeCallback(): void
    {
        $this->channel->basic_consume(
            $this->queueName,
            $consumerTag = $this->consumerTag,
            $noLocal = false,
            $noAck = false,
            $exclusive = false,
            $noWait = false,
            [$this, 'consume']
        );
    }

    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }

    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }
}

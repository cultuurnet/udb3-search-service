<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerNotFoundException;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EventBusForwardingConsumerTest extends TestCase
{
    /**
     * @var AMQPStreamConnection|MockObject
     */
    private $connection;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $exchangeName;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @var EventBus|MockObject
     */
    private $eventBus;

    /**
     * @var DeserializerLocatorInterface|MockObject
     */
    private $deserializerLocator;

    /**
     * @var AbstractChannel|MockObject
     */
    private $channel;

    /**
     * Seconds to delay the actual consumption of the message after it arrived.
     *
     * @var int
     */
    private $delay;

    /**
     * @var EventBusForwardingConsumer
     */
    private $eventBusForwardingConsumer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var DeserializerInterface|MockObject
     */
    private $deserializer;


    protected function setUp(): void
    {
        $this->connection = $this->createMock(AMQPStreamConnection::class);

        $this->delay = 1;

        $this->queueName = 'my-queue';
        $this->exchangeName = 'my-exchange';
        $this->consumerTag = 'my-tag';
        $this->eventBus = $this->createMock(EventBus::class);
        $this->deserializerLocator = $this->createMock(DeserializerLocatorInterface::class);
        $this->channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $this->connection->expects($this->any())
            ->method('channel')
            ->willReturn($this->channel);

        $this->eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $this->exchangeName,
            $this->queueName,
            $this->delay
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventBusForwardingConsumer->setLogger($this->logger);

        $this->deserializer = $this->createMock(DeserializerInterface::class);
    }

    /**
     * @test
     */
    public function it_can_get_the_connection()
    {
        $this->channel->expects($this->once())
            ->method('basic_qos')
            ->with(0, 4, true);

        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $this->exchangeName,
            $this->queueName
        );

        $expectedConnection = $this->connection;

        $this->assertEquals($expectedConnection, $eventBusForwardingConsumer->getConnection());
    }

    /**
     * @test
     */
    public function it_can_publish_the_message_on_the_event_bus()
    {
        $context = [];
        $context['correlation_id'] = 'my-correlation-id-123';

        $expectedMetadata = new Metadata($context);
        $expectedPayload = '';

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                function ($domainEventStream) use ($expectedMetadata, $expectedPayload) {
                    /** @var DomainEventStream $domainEventStream */
                    $iterator = $domainEventStream->getIterator();
                    $domainMessage = $iterator->offsetGet(0);
                    $actualMetadata = $domainMessage->getMetadata();
                    $actualPayload = $domainMessage->getPayload();
                    if ($actualMetadata == $expectedMetadata && $actualPayload == $expectedPayload) {
                        return true;
                    } else {
                        return false;
                    }
                }
            ));

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with('')
            ->willReturn('');

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_consuming()
    {
        $context = [];
        $context['correlation_id'] = 'my-correlation-id-123';

        $this->logger
            ->expects($this->at(0))
            ->method('info')
            ->with(
                'received message with content-type application/vnd.cultuurnet.udb3-events.dummy-event+json',
                $context
            );

        $this->logger
            ->expects($this->at(1))
            ->method('info')
            ->with(
                'passing on message to event bus',
                $context
            );

        $this->logger
            ->expects($this->at(2))
            ->method('info')
            ->with(
                'message acknowledged',
                $context
            );

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willReturn($this->deserializer);

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_rejects_the_massage_when_an_error_occurs()
    {
        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new \InvalidArgumentException('Deserializerlocator error'));

        $this->channel->expects($this->once())
            ->method('basic_reject')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_rejecting_a_message()
    {
        $context = [];
        $context['correlation_id'] = 'my-correlation-id-123';

        $this->logger
            ->expects($this->at(0))
            ->method('info')
            ->with(
                'received message with content-type application/vnd.cultuurnet.udb3-events.dummy-event+json',
                $context
            );

        $this->logger
            ->expects($this->at(1))
            ->method('error')
            ->with(
                'Deserializerlocator error',
                $context + ['exception' => new \InvalidArgumentException('Deserializerlocator error')]
            );

        $this->logger
            ->expects($this->at(2))
            ->method('info')
            ->with(
                'message rejected',
                $context
            );

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new \InvalidArgumentException('Deserializerlocator error'));

        $this->channel->expects($this->once())
            ->method('basic_reject')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_automatically_acknowledges_when_no_deserializer_was_found(): void
    {
        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new DeserializerNotFoundException());

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }
}

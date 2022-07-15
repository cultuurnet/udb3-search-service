<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Closure;
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
    private MockObject $eventBus;
    private MockObject $deserializer;
    private MockObject $deserializerLocator;
    private MockObject $channel;
    private MockObject $logger;
    private Closure $consumeCallback;

    protected function setUp(): void
    {
        $this->eventBus = $this->createMock(EventBus::class);
        $this->deserializer = $this->createMock(DeserializerInterface::class);
        $this->deserializerLocator = $this->createMock(DeserializerLocatorInterface::class);
        $this->channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        // Mock the basic_consume call on the AMQPChannel that will be returned by the AMQPStreamConnection mock
        // injected in EventBusForwardingConsumer, so we can store the callback that gets registered for message
        // consumption on the AMQPChannel. Then we can test the callback by calling it with call_user_func().
        $this->channel->expects($this->once())
            ->method('basic_consume')
            ->willReturnCallback(
                function (
                    string $queueName,
                    string $consumerTag,
                    bool $noLocal,
                    bool $noAck,
                    bool $exclusive,
                    bool $noWait,
                    Closure $consumeCallback
                ): void {
                    $this->consumeCallback = $consumeCallback;
                }
            );

        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection->expects($this->any())
            ->method('channel')
            ->willReturn($this->channel);

        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $connection,
            $this->eventBus,
            $this->deserializerLocator,
            'my-tag',
            'my-exchange',
            'my-queue',
            1
        );
        $eventBusForwardingConsumer->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_can_publish_the_message_on_the_event_bus(): void
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

        call_user_func($this->consumeCallback, $message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_consuming(): void
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

        call_user_func($this->consumeCallback, $message);
    }

    /**
     * @test
     */
    public function it_rejects_the_massage_when_an_error_occurs(): void
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

        call_user_func($this->consumeCallback, $message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_rejecting_a_message(): void
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

        call_user_func($this->consumeCallback, $message);
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

        call_user_func($this->consumeCallback, $message);
    }
}

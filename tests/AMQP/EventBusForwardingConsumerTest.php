<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use InvalidArgumentException;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Closure;
use CultuurNet\UDB3\Search\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Search\Deserializer\DeserializerNotFoundException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EventBusForwardingConsumerTest extends TestCase
{
    private const LOG_RECEIVED_MSG = 'received message with content-type application/vnd.cultuurnet.udb3-events.dummy-event+json';
    private const LOG_PASSING_MSG = 'passing on message to event bus';
    private const LOG_ACK_MSG = 'message acknowledged';
    private const LOG_ERROR = 'Deserializerlocator error';
    private const LOG_REJECTED = 'message rejected';

    /** @var EventBus&MockObject */
    private $eventBus;

    /** @var DeserializerInterface&MockObject */
    private $deserializer;

    /** @var DeserializerLocatorInterface&MockObject */
    private $deserializerLocator;

    /** @var AMQPChannel&MockObject */
    private $channel;

    /** @var LoggerInterface&MockObject */
    private $logger;

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
            '#',
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
                function ($domainEventStream) use ($expectedMetadata, $expectedPayload): bool {
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
        $messageLog = [];
        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->willReturnCallback(function (string $message, $context) use (&$messageLog): void {
                $this->assertEquals(
                    ['correlation_id' => 'my-correlation-id-123'],
                    $context
                );

                switch ($message) {
                    case self::LOG_RECEIVED_MSG:
                    case self::LOG_PASSING_MSG:
                    case self::LOG_ACK_MSG:
                        $messageLog[$message] = true;
                        break;
                    default:
                        $this->fail('Unexpected message: ' . $message);
                }
            });

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

        $this->assertArrayHasKey(self::LOG_RECEIVED_MSG, $messageLog);
        $this->assertArrayHasKey(self::LOG_PASSING_MSG, $messageLog);
        $this->assertArrayHasKey(self::LOG_ACK_MSG, $messageLog);
    }

    /**
     * @test
     */
    public function it_rejects_the_massage_when_an_error_occurs(): void
    {
        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new InvalidArgumentException('Deserializerlocator error'));

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
        $messageLog = [];
        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, $context) use (&$messageLog): void {
                if ($message === self::LOG_RECEIVED_MSG || $message === self::LOG_REJECTED) {
                    $this->assertEquals(
                        ['correlation_id' => 'my-correlation-id-123'],
                        $context
                    );

                    $messageLog[$message] = true;
                } else {
                    $this->fail('Unexpected message: ' . $message);
                }
            });
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->willReturnCallback(function (string $message, $context) use (&$messageLog): void {
                if ($message !== self::LOG_ERROR) {
                    $this->fail('Unexpected error message: ' . $message);
                }

                $this->assertEquals(
                    ['correlation_id' => 'my-correlation-id-123', 'exception' => new InvalidArgumentException('Deserializerlocator error')],
                    $context
                );

                // This check is not technicly needed because there is only 1 call, but I kept it to keep it the same as the info() calls.
                $messageLog[$message] = true;
            });

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new InvalidArgumentException('Deserializerlocator error'));

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

        $this->assertArrayHasKey(self::LOG_RECEIVED_MSG, $messageLog);
        $this->assertArrayHasKey(self::LOG_ERROR, $messageLog);
        $this->assertArrayHasKey(self::LOG_REJECTED, $messageLog);
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

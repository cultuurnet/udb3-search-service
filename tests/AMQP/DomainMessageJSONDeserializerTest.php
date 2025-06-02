<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Search\FileReader;
use InvalidArgumentException;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Search\AMQP\Dummies\DummyEvent;
use CultuurNet\UDB3\Search\AMQP\Dummies\DummyEventNotSerializable;
use PHPUnit\Framework\TestCase;

final class DomainMessageJSONDeserializerTest extends TestCase
{
    protected DomainMessageJSONDeserializer $domainMessageJSONDeserializer;

    protected function setUp(): void
    {
        $this->domainMessageJSONDeserializer = new DomainMessageJSONDeserializer(
            DummyEvent::class
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_if_payloadclass_does_not_implement_SerializableInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Class \'CultuurNet\UDB3\Search\AMQP\Dummies\DummyEventNotSerializable\' does not implement ' .
            Serializable::class
        );

        new DomainMessageJSONDeserializer(DummyEventNotSerializable::class);
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_domain_message(): void
    {
        $jsonData = FileReader::read(__DIR__ . '/Dummies/domain-message-dummy-event.json');

        $expectedDomainMessage = new DomainMessage(
            'message-id-123',
            0,
            new Metadata(),
            new DummyEvent('foo', 'bla'),
            DateTime::fromString('2016-03-25')
        );

        $domainMessage = $this->domainMessageJSONDeserializer->deserialize($jsonData);

        $this->assertEquals($expectedDomainMessage, $domainMessage);
    }
}

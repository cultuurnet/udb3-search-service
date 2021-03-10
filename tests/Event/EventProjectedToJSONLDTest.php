<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Event;

use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

final class EventProjectedToJSONLDTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_and_deserialize(): void
    {
        $eventId = Uuid::uuid4();
        $iri = 'example.com/' . $eventId->toString();

        $serializedData = [
            'item_id' => $eventId->toString(),
            'iri' => $iri,
        ];
        $event = new EventProjectedToJSONLD($eventId->toString(), $iri);

        $this->assertEquals($serializedData, $event->serialize());
        $this->assertEquals($event, EventProjectedToJSONLD::deserialize($serializedData));
    }

    /**
     * @test
     */
    public function it_exposes_its_values(): void
    {
        $eventId = Uuid::uuid4();
        $iri = 'example.com/' . $eventId->toString();

        $event = new EventProjectedToJSONLD($eventId->toString(), $iri);

        $this->assertEquals($eventId, $event->getItemId());
        $this->assertEquals($iri, $event->getIri());
    }
}

<?php

namespace CultuurNet\UDB3\Search\Event;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class EventProjectedToJSONLDTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_and_deserialize(): void
    {
        $eventId = UUID::generateAsString();
        $iri = 'example.com/' . $eventId;

        $serializedData = [
            'item_id' => $eventId,
            'iri' => $iri,
        ];
        $event = new EventProjectedToJSONLD($eventId, $iri);

        $this->assertEquals($serializedData, $event->serialize());
        $this->assertEquals($event, EventProjectedToJSONLD::deserialize($serializedData));
    }

    /**
     * @test
     */
    public function it_exposes_its_values(): void
    {
        $eventId = UUID::generateAsString();
        $iri = 'example.com/' . $eventId;

        $event = new EventProjectedToJSONLD($eventId, $iri);

        $this->assertEquals($eventId, $event->getItemId());
        $this->assertEquals($iri, $event->getIri());
    }
}

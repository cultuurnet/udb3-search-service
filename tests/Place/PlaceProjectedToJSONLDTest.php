<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Place;

use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

final class PlaceProjectedToJSONLDTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_and_deserialize(): void
    {
        $placeId = Uuid::uuid4();
        $iri = 'example.com/' . $placeId->toString();

        $serializedData = [
            'item_id' => $placeId->toString(),
            'iri' => $iri,
        ];
        $event = new PlaceProjectedToJSONLD($placeId->toString(), $iri);

        $this->assertEquals($serializedData, $event->serialize());
        $this->assertEquals($event, PlaceProjectedToJSONLD::deserialize($serializedData));
    }

    /**
     * @test
     */
    public function it_exposes_its_values(): void
    {
        $placeId = Uuid::uuid4();
        $iri = 'example.com/' . $placeId->toString();

        $event = new PlaceProjectedToJSONLD($placeId->toString(), $iri);

        $this->assertEquals($placeId, $event->getItemId());
        $this->assertEquals($iri, $event->getIri());
    }
}

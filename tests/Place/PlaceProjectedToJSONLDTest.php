<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Place;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

final class PlaceProjectedToJSONLDTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_and_deserialize(): void
    {
        $placeId = UUID::generateAsString();
        $iri = 'example.com/' . $placeId;

        $serializedData = [
            'item_id' => $placeId,
            'iri' => $iri,
        ];
        $event = new PlaceProjectedToJSONLD($placeId, $iri);

        $this->assertEquals($serializedData, $event->serialize());
        $this->assertEquals($event, PlaceProjectedToJSONLD::deserialize($serializedData));
    }

    /**
     * @test
     */
    public function it_exposes_its_values(): void
    {
        $placeId = UUID::generateAsString();
        $iri = 'example.com/' . $placeId;

        $event = new PlaceProjectedToJSONLD($placeId, $iri);

        $this->assertEquals($placeId, $event->getItemId());
        $this->assertEquals($iri, $event->getIri());
    }
}

<?php

namespace CultuurNet\UDB3\Search\Place;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;

class PlaceSearchProjectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonDocumentIndexServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexService;

    /**
     * @var PlaceSearchProjector
     */
    private $projector;

    public function setUp()
    {
        $this->indexService = $this->createMock(JsonDocumentIndexServiceInterface::class);
        $this->projector = new PlaceSearchProjector($this->indexService);
    }

    /**
     * @test
     */
    public function it_indexes_new_and_updated_places()
    {
        $placeId = '23017cb7-e515-47b4-87c4-780735acc942';
        $placeUrl = 'place/' . $placeId;

        $domainMessage = new DomainMessage(
            $placeId,
            0,
            new Metadata(),
            new PlaceProjectedToJSONLD($placeId, $placeUrl),
            DateTime::now()
        );

        $this->indexService->expects($this->once())
            ->method('index')
            ->with($placeId, $placeUrl);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_place_deleted()
    {
        $placeId = '23017cb7-e515-47b4-87c4-780735acc942';

        $domainMessage = new DomainMessage(
            $placeId,
            0,
            new Metadata(),
            new PlaceDeleted($placeId),
            DateTime::now()
        );

        $this->indexService->expects($this->never())
            ->method('remove')
            ->with($placeId);

        $this->projector->handle($domainMessage);
    }
}

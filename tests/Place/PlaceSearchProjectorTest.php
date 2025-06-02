<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Place;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PlaceSearchProjectorTest extends TestCase
{
    /**
     * @var JsonDocumentIndexServiceInterface&MockObject
     */
    private $indexService;


    private PlaceSearchProjector $projector;

    protected function setUp(): void
    {
        $this->indexService = $this->createMock(JsonDocumentIndexServiceInterface::class);
        $this->projector = new PlaceSearchProjector($this->indexService);
    }

    /**
     * @test
     */
    public function it_indexes_new_and_updated_places(): void
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
}

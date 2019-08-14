<?php

namespace CultuurNet\UDB3\Search\Organizer;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrganizerSearchProjectorTest extends TestCase
{
    /**
     * @var JsonDocumentIndexServiceInterface|MockObject
     */
    private $indexService;

    /**
     * @var OrganizerSearchProjector
     */
    private $projector;

    public function setUp()
    {
        $this->indexService = $this->createMock(JsonDocumentIndexServiceInterface::class);
        $this->projector = new OrganizerSearchProjector($this->indexService);
    }

    /**
     * @test
     */
    public function it_indexes_new_and_updated_organizers()
    {
        $organizerId = '23017cb7-e515-47b4-87c4-780735acc942';
        $organizerUrl = 'organizer/' . $organizerId;

        $domainMessage = new DomainMessage(
            $organizerId,
            0,
            new Metadata(),
            new OrganizerProjectedToJSONLD($organizerId, $organizerUrl),
            DateTime::now()
        );

        $this->indexService->expects($this->once())
            ->method('index')
            ->with($organizerId, $organizerUrl);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_organizer_deleted()
    {
        $organizerId = '23017cb7-e515-47b4-87c4-780735acc942';

        $domainMessage = new DomainMessage(
            $organizerId,
            0,
            new Metadata(),
            new OrganizerDeleted($organizerId),
            DateTime::now()
        );

        $this->indexService->expects($this->never())
            ->method('remove')
            ->with($organizerId);

        $this->projector->handle($domainMessage);
    }
}

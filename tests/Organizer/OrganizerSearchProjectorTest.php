<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrganizerSearchProjectorTest extends TestCase
{
    /**
     * @var JsonDocumentIndexServiceInterface&MockObject
     */
    private $indexService;


    private OrganizerSearchProjector $projector;

    protected function setUp(): void
    {
        $this->indexService = $this->createMock(JsonDocumentIndexServiceInterface::class);
        $this->projector = new OrganizerSearchProjector($this->indexService);
    }

    /**
     * @test
     */
    public function it_indexes_new_and_updated_organizers(): void
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
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Event;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EventSearchProjectorTest extends TestCase
{
    /**
     * @var JsonDocumentIndexServiceInterface|MockObject
     */
    private $indexService;


    private EventSearchProjector $projector;

    protected function setUp(): void
    {
        $this->indexService = $this->createMock(JsonDocumentIndexServiceInterface::class);
        $this->projector = new EventSearchProjector($this->indexService);
    }

    /**
     * @test
     */
    public function it_indexes_new_and_updated_events(): void
    {
        $eventId = '23017cb7-e515-47b4-87c4-780735acc942';
        $eventUrl = 'event/' . $eventId;

        $domainMessage = new DomainMessage(
            $eventId,
            0,
            new Metadata(),
            new EventProjectedToJSONLD($eventId, $eventUrl),
            DateTime::now()
        );

        $this->indexService->expects($this->once())
            ->method('index')
            ->with($eventId, $eventUrl);

        $this->projector->handle($domainMessage);
    }
}

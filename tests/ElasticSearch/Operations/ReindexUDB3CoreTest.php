<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Search\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ReindexUDB3CoreTest extends AbstractReindexUDB3CoreTest
{
    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @return ReindexUDB3Core
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new ReindexUDB3Core(
            $client,
            $logger,
            $this->getEventBus(),
            '1m',
            10
        );
    }
}

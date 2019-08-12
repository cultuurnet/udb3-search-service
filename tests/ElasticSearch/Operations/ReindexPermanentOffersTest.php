<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ReindexPermanentOffersTest extends AbstractReindexUDB3CoreTest
{
    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @return ReindexPermanentOffers
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new ReindexPermanentOffers(
            $client,
            $logger,
            $this->getEventBus(),
            '1m',
            10
        );
    }
}

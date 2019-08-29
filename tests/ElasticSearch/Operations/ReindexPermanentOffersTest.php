<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

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

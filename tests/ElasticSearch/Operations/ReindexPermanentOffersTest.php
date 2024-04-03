<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class ReindexPermanentOffersTest extends AbstractReindexUDB3CoreTest
{
    /**
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

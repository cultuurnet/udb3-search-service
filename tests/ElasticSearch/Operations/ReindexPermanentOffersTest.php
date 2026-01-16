<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;

final class ReindexPermanentOffersTest extends AbstractReindexUDB3CoreTest
{
    protected function createOperation(ClientInterface $client, LoggerInterface $logger): ReindexPermanentOffers
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

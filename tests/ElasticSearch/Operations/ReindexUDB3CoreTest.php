<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

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

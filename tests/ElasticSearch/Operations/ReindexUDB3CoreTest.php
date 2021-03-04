<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class ReindexUDB3CoreTest extends AbstractReindexUDB3CoreTest
{
    /**
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

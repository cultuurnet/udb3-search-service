<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use Psr\Log\LoggerInterface;

final class ReindexUDB3CoreTest extends AbstractReindexUDB3CoreTest
{
    protected function createOperation(ElasticSearchClientInterface $client, LoggerInterface $logger): ReindexUDB3Core
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

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations\ElasticSearch5;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\ReindexPermanentOffers;
use CultuurNet\UDB3\Search\ElasticSearch5Test;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class ReindexPermanentOffersTest extends AbstractReindexUDB3CoreTest implements ElasticSearch5Test
{
    protected function createOperation(Client $client, LoggerInterface $logger): ReindexPermanentOffers
    {
        $operation = new ReindexPermanentOffers(
            $client,
            $logger,
            $this->getEventBus(),
            '1m',
            10
        );
        $operation->enableElasticSearch5CompatibilityMode();
        return $operation;
    }
}

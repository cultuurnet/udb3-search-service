<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearch5Compatibility;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

abstract class AbstractElasticSearchOperation
{
    use ElasticSearch5Compatibility;

    protected Client $client;

    protected LoggerInterface $logger;

    public function __construct(
        Client $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }
}

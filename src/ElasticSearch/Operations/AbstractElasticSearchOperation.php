<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractElasticSearchOperation
{
    protected ClientInterface $client;

    protected LoggerInterface $logger;


    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }
}

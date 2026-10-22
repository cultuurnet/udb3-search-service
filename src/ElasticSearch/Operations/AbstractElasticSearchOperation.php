<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use Psr\Log\LoggerInterface;

abstract class AbstractElasticSearchOperation
{
    protected ElasticSearchClient $client;

    protected LoggerInterface $logger;

    public function __construct(
        ElasticSearchClient $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }
}

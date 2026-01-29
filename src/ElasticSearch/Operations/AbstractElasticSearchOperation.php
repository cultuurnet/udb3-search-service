<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractElasticSearchOperation
{
    protected ElasticSearchClientInterface $client;

    protected LoggerInterface $logger;


    public function __construct(
        ElasticSearchClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }
}

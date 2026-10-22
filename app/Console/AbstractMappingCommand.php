<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;

abstract class AbstractMappingCommand extends AbstractElasticSearchCommand
{
    protected string $indexName;

    public function __construct(ElasticSearchClient $client, string $indexName)
    {
        parent::__construct($client);
        $this->indexName = $indexName;
    }
}

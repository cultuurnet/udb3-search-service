<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Elasticsearch\Client;

abstract class AbstractMappingCommand extends AbstractElasticSearchCommand
{
    protected string $indexName;

    public function __construct(Client $client, string $indexName)
    {
        parent::__construct($client);
        $this->indexName = $indexName;
    }
}

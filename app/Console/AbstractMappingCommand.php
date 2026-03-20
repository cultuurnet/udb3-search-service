<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Elasticsearch\Client;

abstract class AbstractMappingCommand extends AbstractElasticSearchCommand
{
    protected string $indexName;

    protected string $documentType;

    protected int $elasticsearchVersion;

    public function __construct(Client $client, string $indexName, string $documentType, int $elasticsearchVersion = 5)
    {
        parent::__construct($client);
        $this->indexName = $indexName;
        $this->documentType = $documentType;
        $this->elasticsearchVersion = $elasticsearchVersion;
    }
}

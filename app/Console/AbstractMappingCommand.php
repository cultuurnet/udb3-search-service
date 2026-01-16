<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Elastic\Elasticsearch\ClientInterface;

abstract class AbstractMappingCommand extends AbstractElasticSearchCommand
{
    protected string $indexName;

    protected string $documentType;

    public function __construct(ClientInterface $client, string $indexName, string $documentType)
    {
        parent::__construct($client);
        $this->indexName = $indexName;
        $this->documentType = $documentType;
    }
}

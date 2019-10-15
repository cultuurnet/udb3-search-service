<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Elasticsearch\Client;

abstract class AbstractMappingCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    protected $indexName;

    /**
     * @var string
     */
    protected $documentType;

    public function __construct(Client $client, $indexName, $documentType)
    {
        parent::__construct($client);
        $this->indexName = $indexName;
        $this->documentType = $documentType;
    }
}

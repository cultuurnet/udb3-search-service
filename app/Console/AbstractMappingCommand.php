<?php

declare(strict_types=1);

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

    public function __construct(Client $client, string $indexName, string $documentType)
    {
        parent::__construct($client);
        $this->indexName = $indexName;
        $this->documentType = $documentType;
    }
}

<?php

namespace CultuurNet\UDB3\SearchService\Console;

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

    public function __construct($indexName, $documentType)
    {
        parent::__construct();
        $this->indexName = $indexName;
        $this->documentType = $documentType;
    }
}

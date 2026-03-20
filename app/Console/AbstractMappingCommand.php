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

    protected function guardAgainstEs8(): void
    {
        if ($this->elasticsearchVersion === 8) {
            throw new \RuntimeException(
                'This command is not supported on Elasticsearch 8. Use udb3-core:update-mapping instead.'
            );
        }
    }
}

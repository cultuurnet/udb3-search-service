<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

abstract class AbstractMappingOperation extends AbstractElasticSearchOperation
{
    private int $elasticsearchVersion;

    public function __construct(Client $client, LoggerInterface $logger, int $elasticsearchVersion = 5)
    {
        parent::__construct($client, $logger);
        $this->elasticsearchVersion = $elasticsearchVersion;
    }

    protected function updateMapping(string $indexName, string $documentType, string $mappingFilePath): void
    {
        $params = [
            'index' => $indexName,
            'body' => Json::decodeAssociatively(FileReader::read($mappingFilePath)),
        ];

        if ($this->elasticsearchVersion !== 8) {
            $params['type'] = $documentType;
        }

        $this->client->indices()->putMapping($params);

        $this->logger->info("Mapping for type {$documentType} updated.");
    }
}

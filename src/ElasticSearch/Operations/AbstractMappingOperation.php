<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

abstract class AbstractMappingOperation extends AbstractElasticSearchOperation
{
    protected function updateMapping(string $indexName, string $documentType, string $mappingFilePath): void
    {
        $this->client->indices()->putMapping(
            [
                'index' => $indexName,
                // 'type' => $documentType,
                'body' => Json::decodeAssociatively(
                    FileReader::read($mappingFilePath)
                ),
            ]
        );

        $this->logger->info("Mapping for type {$documentType} updated.");
    }
}

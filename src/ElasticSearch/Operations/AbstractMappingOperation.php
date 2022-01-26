<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\Json;

abstract class AbstractMappingOperation extends AbstractElasticSearchOperation
{
    /**
     * @param string $indexName
     * @param string $documentType
     * @param string $mappingFilePath
     */
    protected function updateMapping($indexName, $documentType, $mappingFilePath)
    {
        $this->client->indices()->putMapping(
            [
                'index' => $indexName,
                'type' => $documentType,
                'body' => Json::decodeAssociatively(
                    file_get_contents($mappingFilePath)
                ),
            ]
        );

        $this->logger->info("Mapping for type {$documentType} updated.");
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

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
                'body' => json_decode(
                    file_get_contents($mappingFilePath),
                    true
                ),
            ]
        );

        $this->logger->info("Mapping for type {$documentType} updated.");
    }
}

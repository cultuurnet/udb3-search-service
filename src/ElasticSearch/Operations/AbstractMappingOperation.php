<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

abstract class AbstractMappingOperation extends AbstractElasticSearchOperation
{
    protected function updateMapping(string $indexName, string $documentType, string $mappingFilePath): void
    {
        $params = [
            'index' => $indexName,
            'body' => Json::decodeAssociatively(FileReader::read($mappingFilePath)),
        ];

        if ($this->usesDocumentTypes()) {
            $params['type'] = $documentType;
        }

        $this->client->indices()->putMapping($params);

        $this->logger->info("Mapping for type {$documentType} updated.");
    }
}

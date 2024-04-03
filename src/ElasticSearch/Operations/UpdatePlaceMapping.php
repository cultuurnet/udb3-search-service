<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class UpdatePlaceMapping extends AbstractMappingOperation
{
    /**
     * @param string $indexName
     * @param string $documentType
     */
    public function run($indexName, $documentType)
    {
        $this->updateMapping(
            $indexName,
            $documentType,
            __DIR__ . '/json/mapping_place.json'
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class UpdateOrganizerMapping extends AbstractMappingOperation
{
    /**
     * @param string $indexName
     * @param string $documentType
     */
    public function run($indexName, $documentType): void
    {
        $this->updateMapping(
            $indexName,
            $documentType,
            __DIR__ . '/json/mapping_organizer.json'
        );
    }
}

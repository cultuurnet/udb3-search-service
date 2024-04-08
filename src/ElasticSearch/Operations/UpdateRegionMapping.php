<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class UpdateRegionMapping extends AbstractMappingOperation
{
    public function run(string $indexName, string $documentType): void
    {
        $this->updateMapping(
            $indexName,
            $documentType,
            __DIR__ . '/json/mapping_region.json'
        );
    }
}

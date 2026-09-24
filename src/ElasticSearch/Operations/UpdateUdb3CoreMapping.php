<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class UpdateUdb3CoreMapping extends AbstractMappingOperation
{
    public function run(string $indexName): void
    {
        $this->updateMapping(
            $indexName,
            __DIR__ . '/json/mapping_udb3_core.json'
        );
    }
}

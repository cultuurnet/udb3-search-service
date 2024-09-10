<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class CheckIndexExists extends AbstractElasticSearchOperation
{
    public function run(string $indexName): bool
    {
        $exists = (bool) $this->client->indices()->exists(['index' => $indexName]);

        if ($exists) {
            $this->logger->info("Index {$indexName} exists.");
        } else {
            $this->logger->info("Index {$indexName} does not exist.");
        }

        return $exists;
    }
}

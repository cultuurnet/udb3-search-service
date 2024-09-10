<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class CreateIndex extends AbstractElasticSearchOperation
{
    public function run(string $indexName, bool $force = false): void
    {
        if ($this->client->indices()->exists(['index' => $indexName])) {
            if (!$force) {
                // Index already exists, but force is disabled so do nothing.
                $this->logger->error("Index {$indexName} already exists!");
                return;
            } else {
                // Index already exists, but force is enabled so delete it.
                $this->client->indices()->delete(['index' => $indexName]);
                $this->logger->info("Existing index {$indexName} deleted.");
            }
        }

        $this->client->indices()->create(['index' => $indexName]);
        $this->logger->info("Index {$indexName} created.");
    }
}

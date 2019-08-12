<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class CreateIndex extends AbstractElasticSearchOperation
{
    /**
     * @param string $indexName
     *   Name of the index to create.
     * @param bool $force
     *   If FALSE, it will check whether an index that matches the new
     *   version already exists, and if so, it stops.
     *   If TRUE, it will drop the index that matches the new version
     *   (if it exists) and then creates the new one.
     */
    public function run($indexName, $force = false)
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

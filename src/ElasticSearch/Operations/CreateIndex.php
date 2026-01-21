<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\Response\Elasticsearch;

final class CreateIndex extends AbstractElasticSearchOperation
{
    public function run(string $indexName, bool $force = false): void
    {
        $doesIndexExists = $this->client->indices()->exists(['index' => $indexName]);
        if (!$doesIndexExists instanceof ElasticSearch) {
            throw new \RuntimeException('Async response type from Elasticsearch client not supported');
        }

        if ($doesIndexExists->asBool()) {
            if (!$force) {
                // Index already exists, but force is disabled so do nothing.
                $this->logger->error("Index {$indexName} already exists!");
                return;
            }

            // Index already exists, but force is enabled so delete it.
            $this->client->indices()->delete(['index' => $indexName]);
            $this->logger->info("Existing index {$indexName} deleted.");
        }

        $this->client->indices()->create(['index' => $indexName]);
        $this->logger->info("Index {$indexName} created.");
    }
}

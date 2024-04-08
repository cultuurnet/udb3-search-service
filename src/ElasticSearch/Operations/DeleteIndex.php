<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class DeleteIndex extends AbstractElasticSearchOperation
{
    /**
     * @param string $indexName
     */
    public function run($indexName): void
    {
        if (!$this->client->indices()->exists(['index' => $indexName])) {
            $this->logger->info("Index {$indexName} does not exist.");
            return;
        }

        $this->client->indices()->delete(['index' => $indexName]);
        $this->logger->info("Index {$indexName} was deleted.");
    }
}

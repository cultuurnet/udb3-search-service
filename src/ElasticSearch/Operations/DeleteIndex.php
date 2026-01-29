<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\Response\Elasticsearch;

final class DeleteIndex extends AbstractElasticSearchOperation
{
    public function run(string $indexName): void
    {
        $doesIndexExists = $this->client->indices()->exists(['index' => $indexName]);
        if (!$doesIndexExists instanceof ElasticSearch) {
            throw new \RuntimeException('Async response type from Elasticsearch client not supported');
        }

        if (!$doesIndexExists->asBool()) {
            $this->logger->info("Index {$indexName} does not exist.");
            return;
        }

        $this->client->indices()->delete(['index' => $indexName]);
        $this->logger->info("Index {$indexName} was deleted.");
    }
}

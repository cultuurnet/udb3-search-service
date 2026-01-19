<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\Response\Elasticsearch;

final class CheckIndexExists extends AbstractElasticSearchOperation
{
    public function run(string $indexName): bool
    {
        $response = $this->client->indices()->exists(['index' => $indexName]);

        if(!$response instanceof ElasticSearch) {
            throw new \RuntimeException('Async response type from Elasticsearch client not supported');
        }

        $exists = $response->asBool();

        if ($exists) {
            $this->logger->info("Index {$indexName} exists.");
        } else {
            $this->logger->info("Index {$indexName} does not exist.");
        }

        return $exists;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

final class ReplaceLocationSuggestionsIndex extends AbstractElasticSearchOperation
{
    private const BULK_SIZE = 1000;

    /**
     * @param iterable<string, array<string, mixed>> $documents
     */
    public function run(
        string $indexName,
        string $indexPattern,
        string $alias,
        iterable $documents,
        int $numberOfShards,
        int $numberOfReplicas
    ): void {
        $this->createIndex($indexName, $numberOfShards, $numberOfReplicas);

        try {
            $sent = $this->indexDocuments($indexName, $documents);
            $this->assertAllDocumentsAreIndexed($indexName, $sent);
        } catch (\Throwable $e) {
            $this->client->indices()->delete(['index' => $indexName]);
            $this->logger->error("Indexing failed, deleted index {$indexName}. The alias {$alias} is unchanged.");
            throw $e;
        }

        $this->logger->info("Indexed {$sent} documents in {$indexName}.");

        $this->moveAlias($alias, $indexName);
        $this->deleteOtherIndices($indexPattern, $indexName);
    }

    private function createIndex(string $indexName, int $numberOfShards, int $numberOfReplicas): void
    {
        // Analyzers can only be set when an index is created, so the whole mapping file is the request body.
        $body = Json::decodeAssociatively(FileReader::read(__DIR__ . '/json/mapping_location_suggestions.json'));
        $body['settings']['number_of_shards'] = $numberOfShards;
        $body['settings']['number_of_replicas'] = $numberOfReplicas;

        $this->client->indices()->create(['index' => $indexName, 'body' => $body]);
        $this->logger->info("Created index {$indexName}.");
    }

    /**
     * @param iterable<string, array<string, mixed>> $documents
     */
    private function indexDocuments(string $indexName, iterable $documents): int
    {
        $count = 0;
        $body = [];

        foreach ($documents as $id => $document) {
            // A full batch is only sent once the next document arrives, so the last batch is never empty.
            if (count($body) === self::BULK_SIZE * 2) {
                $this->client->bulk(['body' => $body]);
                $body = [];
            }

            $body[] = ['index' => ['_index' => $indexName, '_id' => $id]];
            $body[] = $document;
            $count++;

            if ($count % 100000 === 0) {
                $this->logger->info("Sent {$count} documents...");
            }
        }

        if ($body !== []) {
            // The refresh makes the documents countable and searchable before the alias moves to this index.
            $this->client->bulk(['body' => $body, 'refresh' => true]);
        }

        return $count;
    }

    private function assertAllDocumentsAreIndexed(string $indexName, int $sent): void
    {
        // Elasticsearch answers a bulk request with 200 even when single documents fail,
        // so a missing document only shows in the count.
        $response = $this->client->search(['index' => $indexName, 'body' => ['size' => 0, 'track_total_hits' => true]]);
        $indexed = $response['hits']['total']['value'];

        if ($indexed !== $sent) {
            throw new \RuntimeException("Sent {$sent} documents but {$indexName} holds {$indexed}.");
        }
    }

    private function moveAlias(string $alias, string $indexName): void
    {
        $actions = [];
        foreach ((new GetIndexNamesFromAlias($this->client, $this->logger))->run($alias) as $previousIndexName) {
            $actions[] = ['remove' => ['index' => $previousIndexName, 'alias' => $alias]];
        }
        $actions[] = ['add' => ['index' => $indexName, 'alias' => $alias]];

        // One request, so the alias never points to no index at all.
        $this->client->indices()->updateAliases(['body' => ['actions' => $actions]]);
        $this->logger->info("Moved alias {$alias} to {$indexName}.");
    }

    private function deleteOtherIndices(string $indexPattern, string $indexName): void
    {
        // This also removes indices left behind by a run that was killed before it could clean up.
        $indexNames = array_keys($this->client->indices()->get(['index' => $indexPattern]));

        foreach (array_diff($indexNames, [$indexName]) as $otherIndexName) {
            $this->client->indices()->delete(['index' => $otherIndexName]);
            $this->logger->info("Deleted index {$otherIndexName}.");
        }
    }
}

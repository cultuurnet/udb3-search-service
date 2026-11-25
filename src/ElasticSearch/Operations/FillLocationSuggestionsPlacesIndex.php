<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\LocationSuggestions\PlaceDocuments;

/**
 * Indexes every place of the search index once as a location suggestion. After that, the live updates keep them up to date.
 */
final class FillLocationSuggestionsPlacesIndex extends AbstractElasticSearchOperation
{
    private const SCROLL_SIZE = 500;
    private const SCROLL_TTL = '1m';

    public function run(
        string $indexName,
        string $indexPattern,
        string $readAlias,
        string $writeAlias,
        string $sourceIndex,
        PlaceDocuments $placeDocuments,
        int $numberOfShards,
        int $numberOfReplicas
    ): void {
        if (!$this->client->indices()->exists(['index' => $indexName])) {
            $this->createIndex($indexName, $numberOfShards, $numberOfReplicas);
        }

        // First the live updates, so a place that changes while the index fills lands in it too.
        $this->moveAlias($writeAlias, $indexName, $indexPattern, true);

        $sent = $this->indexPlaces($indexName, $sourceIndex, $placeDocuments);
        $this->logger->info("Sent {$sent} places to {$indexName}.");

        $this->moveAlias($readAlias, $indexName, $indexPattern, false);
        $this->deleteOtherIndices($indexPattern, $indexName);
    }

    private function createIndex(string $indexName, int $numberOfShards, int $numberOfReplicas): void
    {
        // The same mapping as the address index, so one query searches both.
        $body = Json::decodeAssociatively(FileReader::read(__DIR__ . '/json/mapping_location_suggestions.json'));
        $body['settings']['number_of_shards'] = $numberOfShards;
        $body['settings']['number_of_replicas'] = $numberOfReplicas;

        $this->client->indices()->create(['index' => $indexName, 'body' => $body]);
        $this->logger->info("Created index {$indexName}.");
    }

    private function indexPlaces(string $indexName, string $sourceIndex, PlaceDocuments $placeDocuments): int
    {
        $results = $this->client->search([
            'index' => $sourceIndex,
            'scroll' => self::SCROLL_TTL,
            'size' => self::SCROLL_SIZE,
            'body' => [
                'query' => ['match' => ['@type' => 'Place']],
                // The search index keeps the JSON-LD as UiTdatabank sent it, the same input as the live updates.
                '_source' => ['originalEncodedJsonLd'],
                'sort' => ['_doc'],
            ],
        ]);

        $sent = 0;
        while (!empty($results['hits']['hits'])) {
            $body = [];
            foreach ($results['hits']['hits'] as $hit) {
                $document = $placeDocuments->create(
                    Json::decodeAssociatively($hit['_source']['originalEncodedJsonLd'] ?? '{}')
                );
                if ($document !== null) {
                    // create and not index: the scroll reads a snapshot from when it started, so a place that changed live
                    // during the fill is already newer in the index and must not be overwritten. A place deleted during the
                    // fill can still come back from the snapshot; its next change removes it again.
                    $body[] = ['create' => ['_index' => $indexName, '_id' => PlaceDocuments::id($hit['_id'])]];
                    $body[] = $document;
                    $sent++;
                }
            }
            if ($body !== []) {
                $this->client->bulk(['body' => $body]);
            }

            $results = $this->client->scroll(['scroll_id' => $results['_scroll_id'], 'scroll' => self::SCROLL_TTL]);
        }

        if (!empty($results['_scroll_id'])) {
            $this->client->clearScroll(['scroll_id' => $results['_scroll_id']]);
        }

        $this->client->indices()->refresh(['index' => $indexName]);

        return $sent;
    }

    private function moveAlias(string $alias, string $indexName, string $indexPattern, bool $isWriteIndex): void
    {
        $actions = [];
        foreach ((new GetIndexNamesFromAlias($this->client, $this->logger))->run($alias) as $previousIndexName) {
            // The read alias also holds the address index. That one stays.
            if ($previousIndexName !== $indexName && fnmatch($indexPattern, $previousIndexName)) {
                $actions[] = ['remove' => ['index' => $previousIndexName, 'alias' => $alias]];
            }
        }
        $actions[] = $isWriteIndex
            ? ['add' => ['index' => $indexName, 'alias' => $alias, 'is_write_index' => true]]
            : ['add' => ['index' => $indexName, 'alias' => $alias]];

        $this->client->indices()->updateAliases(['body' => ['actions' => $actions]]);
        $this->logger->info("Moved alias {$alias} to {$indexName}.");
    }

    private function deleteOtherIndices(string $indexPattern, string $indexName): void
    {
        $indexNames = array_keys($this->client->indices()->get(['index' => $indexPattern]));

        foreach (array_diff($indexNames, [$indexName]) as $otherIndexName) {
            $this->client->indices()->delete(['index' => $otherIndexName]);
            $this->logger->info("Deleted index {$otherIndexName}.");
        }
    }
}

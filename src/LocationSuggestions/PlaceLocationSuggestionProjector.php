<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchRequestFailed;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use Psr\Log\LoggerInterface;

/**
 * Keeps a place's location suggestion up to date, next to the place in the search index.
 */
final readonly class PlaceLocationSuggestionProjector implements EventListener
{
    public function __construct(
        private JsonDocumentFetcher $jsonDocumentFetcher,
        private PlaceDocuments $placeDocuments,
        private ElasticSearchClient $client,
        private LoggerInterface $logger
    ) {
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        if (!$payload instanceof PlaceProjectedToJSONLD) {
            return;
        }

        $jsonDocument = $this->jsonDocumentFetcher->fetch($payload->getItemId(), $payload->getIri());
        if ($jsonDocument === null) {
            // The fetcher already logged why. Removing the suggestion on a passing error would lose it until the next change.
            return;
        }

        $id = PlaceDocuments::id($payload->getItemId());
        $document = $this->placeDocuments->create(Json::decodeAssociatively($jsonDocument->getRawBody()));

        try {
            if ($document === null) {
                // Deleted, not public (anymore), or without coordinates.
                $this->client->delete(['index' => LocationSuggestionsIndices::PLACES_WRITE_ALIAS, 'id' => $id]);
                $this->logger->info("Removed location suggestion {$id}.");
                return;
            }

            // require_alias keeps Elasticsearch from creating an index with the wrong mapping when the places index doesn't exist yet.
            $this->client->index([
                'index' => LocationSuggestionsIndices::PLACES_WRITE_ALIAS,
                'id' => $id,
                'body' => $document,
                'require_alias' => true,
            ]);
            $this->logger->info("Indexed location suggestion {$id}.");
        } catch (ElasticSearchRequestFailed $e) {
            // Not found: the suggestion was already gone, or the places index doesn't exist yet.
            // Anything else fails the message, like the place indexation next to this one, so it is retried.
            if (!$e->isNotFound()) {
                throw $e;
            }
            if ($document !== null) {
                $this->logger->warning(
                    "Could not index location suggestion {$id}: there is no places index yet. Run location-suggestions:index-places.",
                    ['reason' => $e->getReason()]
                );
            }
        }
    }
}

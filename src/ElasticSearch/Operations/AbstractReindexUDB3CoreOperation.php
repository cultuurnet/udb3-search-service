<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Broadway\Serializer\Serializable;
use Exception;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\Event\EventProjectedToJSONLD;
use CultuurNet\UDB3\Search\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractReindexUDB3CoreOperation extends AbstractElasticSearchOperation
{
    private EventBus $eventBus;

    private string $scrollTtl;

    private int $scrollSize;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        EventBus $eventBus,
        string $scrollTtl = '1m',
        int $scrollSize = 50
    ) {
        parent::__construct($client, $logger);
        $this->eventBus = $eventBus;
        $this->scrollTtl = $scrollTtl;
        $this->scrollSize = $scrollSize;
    }

    abstract public function getQueryArray(): array;

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_search_operations.html#_scan_scroll
     */
    public function run(string $indexName): void
    {
        $query = [
            'scroll' => $this->scrollTtl,
            'size' => $this->scrollSize,
            'index' => $indexName,
            'body' => [
                'query' => $this->getQueryArray(),
                'sort' => [
                    '_doc',
                ],
            ],
        ];

        $results = $this->client->search($query);

        while (!empty($results['hits']['hits']) && !empty($results['_scroll_id'])) {
            // Loop over all hits and dispatch a ProjectedToJSONLD event for
            // each one to trigger re-indexation.
            foreach ($results['hits']['hits'] as $hit) {
                $this->dispatchEventForHit($hit);
            }

            // Continue scrolling until there are no more hits or we get an
            // error (missing scroll id).
            $results = $this->client->scroll(
                [
                    'scroll_id' => $results['_scroll_id'],
                    'scroll' => $this->scrollTtl,
                ]
            );
        }

        // Only do cleanup if the response actually contained a scroll id.
        if (!empty($results['_scroll_id'])) {
            $this->logger->info('Cleaning up...');
            $this->client->clearScroll(['scroll_id' => $results['_scroll_id']]);
            $this->logger->info('Closed ElasticSearch scroll.');
        }
    }


    private function dispatchEventForHit(array $hit): void
    {
        if (isset($hit['_type']) && $hit['_type'] == 'region_query') {
            // Skip region queries because they should be re-indexed using
            // the IndexRegionQueries operation. Don't check the document for
            // @id property and/or log anything to avoid an unnecessary flood
            // of irrelevant messages.
            return;
        }

        if (empty($hit['_id'])) {
            $this->logger->error('Skipping hit without _id property.');
            return;
        }
        $id = $hit['_id'];

        if (empty($hit['_type'])) {
            $this->logger->error("Skipping hit {$id} without _type property.");
            return;
        }
        $type = $hit['_type'];

        if (empty($hit['_source'])) {
            $this->logger->error("Skipping hit {$id} without _source property.");
            return;
        }
        $source = $hit['_source'];

        if (empty($source['@id'])) {
            $this->logger->error("Skipping hit {$id} without @id property in _source.");
            return;
        }
        $url = $source['@id'];

        switch ($type) {
            case 'organizer':
                $event = new OrganizerProjectedToJSONLD($id, $url);
                break;

            case 'event':
                $event = new EventProjectedToJSONLD($id, $url);
                break;

            case 'place':
                $event = new PlaceProjectedToJSONLD($id, $url);
                break;
        }

        if (!isset($event)) {
            $this->logger->error("Skipping hit {$id} with unknown document type {$type}.");
            return;
        }

        $eventType = $this->getReadableEventType($event);

        $this->logger->info("Dispatching {$eventType} with id {$id} and url {$url}.");

        $domainMessage = DomainMessage::recordNow($id, 0, new Metadata([]), $event);

        try {
            $this->eventBus->publish(new DomainEventStream([$domainMessage]));
        } catch (Exception $e) {
            $exceptionMessage = $e->getMessage();
            $this->logger->warning("Could not process {$eventType} with id {$id} and url {$url}. {$exceptionMessage}");
        }
    }


    private function getReadableEventType(Serializable $event): string
    {
        $parts = explode('\\', get_class($event));
        return end($parts);
    }
}

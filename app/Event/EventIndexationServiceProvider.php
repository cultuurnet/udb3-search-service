<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Event;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\EventTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use CultuurNet\UDB3\Search\Event\EventSearchProjector;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;

final class EventIndexationServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'event_search_projector',
        'event_bus_subscribers',
    ];

    public function register(): void
    {
        $this->add(
            'event_search_projector',
            function (): EventSearchProjector {
                $transformer = new JsonDocumentTransformer(
                    new EventTransformer(
                        new JsonTransformerPsrLogger(
                            $this->get('logger.amqp.udb3')
                        ),
                        new PathEndIdUrlParser(),
                        $this->get(GeoShapeQueryRegionService::class)
                    )
                );

                $repository = new ElasticSearchDocumentRepository(
                    $this->get(ElasticSearchClientInterface::class),
                    $this->parameter('elasticsearch.event.write_index'),
                    $this->parameter('elasticsearch.event.document_type'),
                    $this->get('elasticsearch_indexation_strategy')
                );

                $service = new TransformingJsonDocumentIndexService(
                    $this->get(JsonDocumentFetcher::class)->withIncludeMetadata()->withEmbedContributors(),
                    $transformer,
                    $repository
                );
                $service->setLogger($this->get('logger.amqp.udb3'));

                return new EventSearchProjector($service);
            },
            'event_bus_subscribers'
        );
    }
}

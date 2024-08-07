<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\PlaceTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\Search\Place\PlaceSearchProjector;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Elasticsearch\Client;

final class PlaceIndexationServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'place_search_projector',
        'event_bus_subscribers',
    ];

    public function register(): void
    {
        $this->add(
            'place_search_projector',
            function (): PlaceSearchProjector {
                $transformer = new JsonDocumentTransformer(
                    new PlaceTransformer(
                        new JsonTransformerPsrLogger(
                            $this->get('logger.amqp.udb3')
                        ),
                        new PathEndIdUrlParser(),
                        $this->get(GeoShapeQueryRegionService::class)
                    )
                );

                $repository = new ElasticSearchDocumentRepository(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.place.write_index'),
                    $this->parameter('elasticsearch.place.document_type'),
                    $this->get('elasticsearch_indexation_strategy')
                );

                $service = new TransformingJsonDocumentIndexService(
                    $this->get(JsonDocumentFetcher::class)->withIncludeMetadata()->withEmbedContributors(),
                    $transformer,
                    $repository
                );
                $service->setLogger($this->get('logger.amqp.udb3'));

                return new PlaceSearchProjector($service);
            },
            'event_bus_subscribers'
        );
    }
}

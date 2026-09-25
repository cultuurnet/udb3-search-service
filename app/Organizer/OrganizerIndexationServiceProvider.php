<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\OrganizerTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchProjector;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Elastic\Elasticsearch\Client;

final class OrganizerIndexationServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'organizer_search_projector',
        'event_bus_subscribers',
    ];

    public function register(): void
    {
        $this->add(
            'organizer_search_projector',
            function (): OrganizerSearchProjector {
                $transformer = new JsonDocumentTransformer(
                    new OrganizerTransformer(
                        new JsonTransformerPsrLogger(
                            $this->get('logger.amqp.udb3')
                        ),
                        new PathEndIdUrlParser(),
                        $this->get(GeoShapeQueryRegionService::class)
                    )
                );

                $repository = new ElasticSearchDocumentRepository(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.organizer.write_index'),
                    $this->parameter('elasticsearch.organizer.document_type'),
                    $this->get('elasticsearch_indexation_strategy')
                );

                $service = new TransformingJsonDocumentIndexService(
                    $this->get(JsonDocumentFetcher::class)->withEmbedContributors(),
                    $transformer,
                    $repository
                );

                $service->setLogger($this->get('logger.amqp.udb3'));

                return new OrganizerSearchProjector($service);
            },
            'event_bus_subscribers'
        );
    }
}

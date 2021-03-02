<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Event;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\EventTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\Event\EventSearchProjector;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcherInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferSearchControllerFactory;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

final class EventServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        'event_controller',
        'event_search_projector',
        'event_bus_subscribers',
    ];

    public function register(): void
    {
        $this->add(
            'event_controller',
            function () {
                /** @var OfferSearchControllerFactory $offerControllerFactory */
                $offerControllerFactory = $this->get(OfferSearchControllerFactory::class);

                return $offerControllerFactory->createFor(
                    $this->parameter('elasticsearch.event.read_index'),
                    $this->parameter('elasticsearch.event.document_type')
                );
            }
        );

        $this->add(
            'event_search_projector',
            function () {
                $service = new TransformingJsonDocumentIndexService(
                    $this->get(JsonDocumentFetcherInterface::class)->withIncludeMetadata(),
                    $this->get('event_elasticsearch_transformer'),
                    $this->get('event_elasticsearch_repository')
                );
                $service->setLogger($this->get('logger.amqp.udb3_consumer'));

                return new EventSearchProjector($service);
            },
            'event_bus_subscribers'
        );

        $this->add(
            'event_elasticsearch_transformer',
            function () {
                return new JsonDocumentTransformer(
                    new EventTransformer(
                        new JsonTransformerPsrLogger(
                            $this->get('logger.amqp.udb3_consumer')
                        ),
                        new PathEndIdUrlParser(),
                        $this->get('offer_region_service')
                    )
                );
            }
        );

        $this->add(
            'event_elasticsearch_repository',
            function () {
                return new ElasticSearchDocumentRepository(
                    $this->get(Client::class),
                    new StringLiteral($this->parameter('elasticsearch.event.write_index')),
                    new StringLiteral($this->parameter('elasticsearch.event.document_type')),
                    $this->get('elasticsearch_indexation_strategy')
                );
            }
        );
    }
}

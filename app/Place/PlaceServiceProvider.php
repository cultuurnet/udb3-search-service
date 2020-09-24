<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Place\PlaceJsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\Search\Place\PlaceSearchProjector;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferSearchControllerFactory;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        'place_controller',
        'place_search_projector',
        'event_bus_subscribers',
    ];

    public function register(): void
    {
        $this->add(
            'place_controller',
            function () {
                /** @var OfferSearchControllerFactory $offerControllerFactory */
                $offerControllerFactory = $this->get(OfferSearchControllerFactory::class);

                return $offerControllerFactory->createFor(
                    $this->parameter('elasticsearch.place.read_index'),
                    $this->parameter('elasticsearch.place.document_type')
                );
            }
        );

        $this->add(
            'place_search_projector',
            function () {
                $service = new TransformingJsonDocumentIndexService(
                    $this->get('http_client'),
                    $this->get('place_elasticsearch_transformer'),
                    $this->get('place_elasticsearch_repository')
                );
                $service->setLogger($this->get('logger.amqp.udb3_consumer'));

                return new PlaceSearchProjector($service);
            },
            'event_bus_subscribers'
        );

        $this->add(
            'place_elasticsearch_transformer',
            function () {
                return new PlaceJsonDocumentTransformer(
                    new PathEndIdUrlParser(),
                    $this->get('offer_region_service'),
                    $this->get('elasticsearch_transformer_logger')
                );
            }
        );

        $this->add(
            'place_elasticsearch_repository',
            function () {
                return new ElasticSearchDocumentRepository(
                    $this->get(Client::class),
                    new StringLiteral($this->parameter('elasticsearch.place.write_index')),
                    new StringLiteral($this->parameter('elasticsearch.place.document_type')),
                    $this->get('elasticsearch_indexation_strategy')
                );
            }
        );
    }
}

<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\OrganizerTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerSearchService;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchProjector;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        OrganizerSearchController::class,
        'organizer_search_projector',
        'event_bus_subscribers',
    ];

    public function register()
    {
        $this->add(
            OrganizerSearchController::class,
            function () {
                $requestParser = (new CompositeOrganizerRequestParser())
                    ->withParser(new WorkflowStatusOrganizerRequestParser())
                    ->withParser(new SortByOrganizerRequestParser());

                return new OrganizerSearchController(
                    $this->get(ApiKeyReaderInterface::class),
                    new ElasticSearchOrganizerQueryBuilder(),
                    new ElasticSearchOrganizerSearchService(
                        $this->get(Client::class),
                        new StringLiteral($this->parameter('elasticsearch.organizer.read_index')),
                        new StringLiteral($this->parameter('elasticsearch.organizer.document_type')),
                        new ElasticSearchPagedResultSetFactory(
                            new NullAggregationTransformer()
                        )
                    ),
                    $requestParser,
                    new LuceneQueryStringFactory()
                );
            }
        );

        $this->add(
            'organizer_search_projector',
            function () {
                $service = new TransformingJsonDocumentIndexService(
                    $this->get(JsonDocumentFetcher::class),
                    $this->get('organizer_elasticsearch_transformer'),
                    $this->get('organizer_elasticsearch_repository')
                );

                $service->setLogger($this->get('logger.amqp.udb3_consumer'));

                return new OrganizerSearchProjector($service);
            },
            'event_bus_subscribers'
        );

        $this->add(
            'organizer_elasticsearch_transformer',
            function () {
                return new JsonDocumentTransformer(
                    new OrganizerTransformer(
                        new JsonTransformerPsrLogger(
                            $this->get('logger.amqp.udb3_consumer')
                        ),
                        new PathEndIdUrlParser()
                    )
                );
            }
        );

        $this->add(
            'organizer_elasticsearch_repository',
            function () {
                return new ElasticSearchDocumentRepository(
                    $this->get(Client::class),
                    new StringLiteral($this->parameter('elasticsearch.organizer.write_index')),
                    new StringLiteral($this->parameter('elasticsearch.organizer.document_type')),
                    $this->get('elasticsearch_indexation_strategy')
                );
            }
        );
    }
}

<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerSearchService;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\OrganizerJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\Search\Http\ResultTransformingPagedCollectionFactory;
use CultuurNet\UDB3\Search\JsonDocument\PassThroughJsonDocumentTransformer;
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
                    new ElasticSearchOrganizerQueryBuilder(),
                    new ElasticSearchOrganizerSearchService(
                        $this->get(Client::class),
                        new StringLiteral($this->parameter('elasticsearch.organizer.read_index')),
                        new StringLiteral($this->parameter('elasticsearch.organizer.document_type')),
                        new JsonDocumentTransformingPagedResultSetFactory(
                            new PassThroughJsonDocumentTransformer(),
                            new ElasticSearchPagedResultSetFactory(
                                new NullAggregationTransformer()
                            )
                        )
                    ),
                    $requestParser,
                    new LuceneQueryStringFactory(),
                    $this->get('paged_collection_factory')
                );
            }
        );
        
        $this->add(
            'paged_collection_factory',
            function () {
                return new ResultTransformingPagedCollectionFactory(
                    $this->get('elasticsearch_result_transformer')
                );
            }
        );
        
        $this->add(
            'elasticsearch_result_transformer',
            function () {
                return new MinimalRequiredInfoJsonDocumentTransformer();
            }
        );
    
    
        $this->add(
            'organizer_search_projector',
            function () {
                $service = new TransformingJsonDocumentIndexService(
                    $this->get('http_client'),
                    $this->get('organizer_elasticsearch_transformer'),
                    $this->get('organizer_elasticsearch_repository')
                );
            
                $service->setLogger($this->get('logger.amqp.udb3_consumer'));
            
                return new OrganizerSearchProjector($service);
            }
        );
    
        $this->add(
            'organizer_elasticsearch_transformer',
            function () {
                return new OrganizerJsonDocumentTransformer(
                    new PathEndIdUrlParser(),
                    $this->get('elasticsearch_transformer_logger')
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

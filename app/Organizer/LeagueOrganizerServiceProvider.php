<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerSearchService;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\Search\Http\ResultTransformingPagedCollectionFactory;
use CultuurNet\UDB3\Search\JsonDocument\PassThroughJsonDocumentTransformer;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Noodlehaus\Config;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo: rename
 */
class LeagueOrganizerServiceProvider extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
        OrganizerSearchController::class,
    ];
    
    public function register()
    {
        $this->add(
            Client::class,
            function () {
                return ClientBuilder::create()
                    ->setHosts(
                        [
                            $this->parameter('elasticsearch.host'),
                        ]
                    )
                    ->build();
            }
        );
        
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
    }
    
    protected function add(string $serviceName, $function)
    {
        $this->getContainer()->add(
            $serviceName,
            $function
        );
    }
    
    protected function parameter(string $parameter)
    {
        return $this->getContainer()->get(Config::class)->get($parameter);
    }
    
    protected function get(string $name)
    {
        return $this->getContainer()->get($name);
    }
}

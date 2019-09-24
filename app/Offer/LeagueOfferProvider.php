<?php declare(strict_types=1);


namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\CompositeAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\LabelsAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NodeMapAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistanceFactory;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use CultuurNet\UDB3\Search\Http\NodeAwareFacetTreeNormalizer;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\AgeRangeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\CompositeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\DistanceOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\DocumentLanguageOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\GeoBoundsOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\SortByOfferRequestParser;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\WorkflowStatusOfferRequestParser;
use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\Search\Http\ResultTransformingPagedCollectionFactory;
use CultuurNet\UDB3\Search\JsonDocument\PassThroughJsonDocumentTransformer;
use CultuurNet\UDB3\Search\Offer\FacetName;
use Elasticsearch\ClientBuilder;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Noodlehaus\Config;
use ValueObjects\StringLiteral\StringLiteral;

class LeagueOfferProvider extends AbstractServiceProvider
{
    protected $provides = [
        OfferSearchController::class,
    ];
    
    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->add(
            OfferSearchController::class,
            function () {
                $requestParser = (new CompositeOfferRequestParser())
                    ->withParser(new AgeRangeOfferRequestParser())
                    ->withParser(new DistanceOfferRequestParser(new ElasticSearchDistanceFactory()))
                    ->withParser(new DocumentLanguageOfferRequestParser())
                    ->withParser(new GeoBoundsOfferRequestParser())
                    ->withParser(new SortByOfferRequestParser())
                    ->withParser(new WorkflowStatusOfferRequestParser());
                return new OfferSearchController(
                    $this->get('auth.api_key_reader'),
                    new InMemoryConsumerRepository(),
                    new ElasticSearchOfferQueryBuilder($this->parameter('elasticsearch.aggregation_size')),
                    $requestParser,
                    new ElasticSearchOfferSearchService(
                        ClientBuilder::create()
                            ->setHosts(
                                [
                                    $this->parameter('elasticsearch.host'),
                                ]
                            )
                            ->build(),
                        new StringLiteral($this->parameter('elasticsearch.offer.read_index')),
                        new StringLiteral($this->parameter('elasticsearch.offer.document_type')),
                        new JsonDocumentTransformingPagedResultSetFactory(
                            new PassThroughJsonDocumentTransformer(),
                            new ElasticSearchPagedResultSetFactory(
                                $this->get('offer_elasticsearch_aggregation_transformer')
                            )
                        )
                    ),
                    new StringLiteral($this->parameter('elasticsearch.region.read_index')),
                    new StringLiteral($this->parameter('elasticsearch.region.document_type')),
                    new LuceneQueryStringFactory(),
                    new NodeAwareFacetTreeNormalizer(),
                    new ResultTransformingPagedCollectionFactory(
                        new MinimalRequiredInfoJsonDocumentTransformer()
                    )
                );
            }
        );
    
        $this->add(
            'auth.api_key_reader',
            function (){
                return new CompositeApiKeyReader(
                    new QueryParameterApiKeyReader('apiKey'),
                    new CustomHeaderApiKeyReader('X-Api-Key')
                );
            }
        );
    
        $this->add('offer_elasticsearch_aggregation_transformer',
            function () {
                $transformer = new CompositeAggregationTransformer();
                $transformer->register($this->get('offer_elasticsearch_region_aggregation_transformer'));
                $transformer->register($this->get('offer_elasticsearch_theme_aggregation_transformer'));
                $transformer->register($this->get('offer_elasticsearch_type_aggregation_transformer'));
                $transformer->register($this->get('offer_elasticsearch_facility_aggregation_transformer'));
                $transformer->register($this->get('offer_elasticsearch_label_aggregation_transformer'));
                return $transformer;
            }
        );
    
        $this->add('offer_elasticsearch_region_aggregation_transformer',
            function () {
                return new NodeMapAggregationTransformer(
                    FacetName::REGIONS(),
                    $this->parameter('facet_mapping_regions')
                );
            }
        );
    
        $this->add('offer_elasticsearch_theme_aggregation_transformer',
            function () {
                return new NodeMapAggregationTransformer(
                    FacetName::THEMES(),
                    $this->parameter('facet_mapping_themes')
                );
            }
        );
    
        $this->add('offer_elasticsearch_type_aggregation_transformer',
            function () {
                return new NodeMapAggregationTransformer(
                    FacetName::TYPES(),
                    $this->parameter('facet_mapping_types')
                );
            }
        );
    
        $this->add('offer_elasticsearch_facility_aggregation_transformer',
            function () {
                return new NodeMapAggregationTransformer(
                    FacetName::FACILITIES(),
                    $this->parameter('facet_mapping_facilities')
                );
            }
        );
    
        $this->add('offer_elasticsearch_label_aggregation_transformer',
            function () {
                return new LabelsAggregationTransformer(
                    FacetName::LABELS()
                );
            }
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
    protected function add(string $serviceName, $function)
    {
        $this->getContainer()->add(
            $serviceName,
            $function
        );
    }
    
}
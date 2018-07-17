<?php

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\CompositeAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NodeMapAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\LabelsAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\GeoShapeQueryOfferRegionService;
use CultuurNet\UDB3\Search\JsonDocument\PassThroughJsonDocumentTransformer;
use CultuurNet\UDB3\Search\Offer\FacetName;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['offer_elasticsearch_query_builder'] = $app->share(
            function () {
                return new ElasticSearchOfferQueryBuilder();
            }
        );

        $app['offer_elasticsearch_service_factory'] = $app->protect(
            function ($readIndex, $documentType) use ($app) {
                return new ElasticSearchOfferSearchService(
                    $app['elasticsearch_client'],
                    new StringLiteral($readIndex),
                    new StringLiteral($documentType),
                    new JsonDocumentTransformingPagedResultSetFactory(
                        new PassThroughJsonDocumentTransformer(),
                        new ElasticSearchPagedResultSetFactory(
                            $app['offer_elasticsearch_aggregation_transformer']
                        )
                    )
                );
            }
        );

        $app['offer_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return $app['offer_elasticsearch_service_factory'](
                    $app['elasticsearch.offer.read_index'],
                    $app['elasticsearch.offer.document_type']
                );
            }
        );

        $app['offer_elasticsearch_aggregation_transformer'] = $app->share(
            function (Application $app) {
                $transformer = new CompositeAggregationTransformer();
                $transformer->register($app['offer_elasticsearch_region_aggregation_transformer']);
                $transformer->register($app['offer_elasticsearch_theme_aggregation_transformer']);
                $transformer->register($app['offer_elasticsearch_type_aggregation_transformer']);
                $transformer->register($app['offer_elasticsearch_facility_aggregation_transformer']);
                $transformer->register($app['offer_elasticsearch_label_aggregation_transformer']);
                return $transformer;
            }
        );

        $app['offer_elasticsearch_region_aggregation_transformer'] = $app->share(
            function (Application $app) {
                return new NodeMapAggregationTransformer(
                    FacetName::REGIONS(),
                    $app['elasticsearch.facet_mapping.regions']
                );
            }
        );

        $app['offer_elasticsearch_theme_aggregation_transformer'] = $app->share(
            function (Application $app) {
                return new NodeMapAggregationTransformer(
                    FacetName::THEMES(),
                    $app['elasticsearch.facet_mapping.themes']
                );
            }
        );

        $app['offer_elasticsearch_type_aggregation_transformer'] = $app->share(
            function (Application $app) {
                return new NodeMapAggregationTransformer(
                    FacetName::TYPES(),
                    $app['elasticsearch.facet_mapping.types']
                );
            }
        );

        $app['offer_elasticsearch_facility_aggregation_transformer'] = $app->share(
            function (Application $app) {
                return new NodeMapAggregationTransformer(
                    FacetName::FACILITIES(),
                    $app['elasticsearch.facet_mapping.facilities']
                );
            }
        );

        $app['offer_elasticsearch_label_aggregation_transformer'] = $app->share(
            function (Application $app) {
                return new LabelsAggregationTransformer(
                    FacetName::LABELS()
                );
            }
        );

        $app['offer_region_service'] = $app->share(
            function (Application $app) {
                return new GeoShapeQueryOfferRegionService(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.region.read_index'])
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}

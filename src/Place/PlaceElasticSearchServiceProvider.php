<?php

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Place\PlaceJsonDocumentTransformer;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['place_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return $app['offer_elasticsearch_service_factory'](
                    $app['elasticsearch.place.read_index'],
                    $app['elasticsearch.place.document_type']
                );
            }
        );

        $app['place_elasticsearch_repository'] = $app->share(
            function (Application $app) {
                return new ElasticSearchDocumentRepository(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.place.write_index']),
                    new StringLiteral($app['elasticsearch.place.document_type'])
                );
            }
        );

        $app['place_elasticsearch_transformer'] = $app->share(
            function (Application $app) {
                return new PlaceJsonDocumentTransformer(
                    new PathEndIdUrlParser(),
                    $app['offer_region_service'],
                    $app['elasticsearch_transformer_logger']
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

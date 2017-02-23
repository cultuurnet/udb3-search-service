<?php

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
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
        $app['place_elasticsearch_repository'] = $app->share(
            function (Application $app) {
                return new ElasticSearchDocumentRepository(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.place.index_name']),
                    new StringLiteral($app['elasticsearch.place.document_type'])
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

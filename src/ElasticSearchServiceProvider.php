<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use Elasticsearch\ClientBuilder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['elasticsearch_client'] = $app->share(
            function (Application $app) {
                return ClientBuilder::create()
                    ->setHosts(
                        [
                            $app['elasticsearch.host'],
                        ]
                    )
                    ->build();
            }
        );

        $app['elasticsearch_query_string_factory'] = $app->share(
            function () {
                return new LuceneQueryStringFactory();
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

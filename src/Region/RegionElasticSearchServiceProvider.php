<?php

namespace CultuurNet\UDB3\SearchService\Region;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Region\ElasticSearchRegionSearchService;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class RegionElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['region_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return new ElasticSearchRegionSearchService(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.region.index_name']),
                    new StringLiteral($app['elasticsearch.region.document_type']),
                    new ElasticSearchPagedResultSetFactory()
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

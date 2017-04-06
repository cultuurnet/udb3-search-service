<?php

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\ResultSetJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\PercolatorOfferRegionService;
use CultuurNet\UDB3\SearchService\EmptyOfferRegionService;
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
        $app['offer_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return new ElasticSearchOfferSearchService(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.offer.read_index']),
                    new StringLiteral($app['elasticsearch.offer.document_type']),
                    new JsonDocumentTransformingPagedResultSetFactory(
                        new ResultSetJsonDocumentTransformer(),
                        new ElasticSearchPagedResultSetFactory()
                    )
                );
            }
        );

        $app['offer_region_service'] = $app->share(
            function (Application $app) {
                // Configuration here is tricky. It might seem like we should
                // use the read alias for the offer index since we're reading
                // data. But actually we need to use the write alias because we
                // need to read data that is relevant when (re-)indexing offer
                // documents (= writing), and the read alias might be outdated
                // during a migration.
                return new PercolatorOfferRegionService(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.offer.write_index'])
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

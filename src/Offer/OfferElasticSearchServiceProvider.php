<?php

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\ResultSetJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
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
                    new StringLiteral($app['elasticsearch.offer.index_name']),
                    new StringLiteral($app['elasticsearch.offer.document_type']),
                    new JsonDocumentTransformingPagedResultSetFactory(
                        new ResultSetJsonDocumentTransformer(),
                        new ElasticSearchPagedResultSetFactory()
                    )
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

<?php

namespace CultuurNet\UDB3\SearchService\Event;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\Event\EventJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class EventElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['event_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return $app['offer_elasticsearch_service_factory'](
                    $app['elasticsearch.event.read_index'],
                    $app['elasticsearch.event.document_type']
                );
            }
        );

        $app['event_elasticsearch_repository'] = $app->share(
            function (Application $app) {
                return new ElasticSearchDocumentRepository(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.event.write_index']),
                    new StringLiteral($app['elasticsearch.event.document_type'])
                );
            }
        );

        $app['event_elasticsearch_transformer'] = $app->share(
            function (Application $app) {
                return new EventJsonDocumentTransformer(
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

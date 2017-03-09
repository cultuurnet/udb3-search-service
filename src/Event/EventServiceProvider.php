<?php

namespace CultuurNet\UDB3\SearchService\Event;

use CultuurNet\UDB3\Search\ElasticSearch\Event\EventJsonDocumentTransformer;
use CultuurNet\UDB3\Search\Event\EventSearchProjector;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['event_search_projector'] = $app->share(
            function (Application $app) {
                return new EventSearchProjector(
                    new TransformingJsonDocumentIndexService(
                        $app['http_client'],
                        $app['event_elasticsearch_transformer'],
                        $app['event_elasticsearch_repository']
                    )
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

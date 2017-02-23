<?php

namespace CultuurNet\UDB3\SearchService\Event;

use CultuurNet\UDB3\Search\Event\EventSearchProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['event_search_projector'] = $app->share(
            function (Application $app) {
                return new EventSearchProjector(
                    $app['event_elasticsearch_repository'],
                    $app['http_client']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

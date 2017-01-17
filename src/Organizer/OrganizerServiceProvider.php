<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\OrganizerSearchProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['organizer_search_projector'] = $app->share(
            function (Application $app) {
                return new OrganizerSearchProjector(
                    $app['organizer_elasticsearch_repository'],
                    $app['http_client']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

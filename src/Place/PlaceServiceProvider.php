<?php

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\Place\PlaceSearchProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['place_search_projector'] = $app->share(
            function (Application $app) {
                return new PlaceSearchProjector(
                    $app['place_elasticsearch_repository'],
                    $app['http_client']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

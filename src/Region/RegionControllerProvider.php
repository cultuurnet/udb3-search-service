<?php

namespace CultuurNet\UDB3\SearchService\Region;

use CultuurNet\UDB3\Search\Http\RegionSearchController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class RegionControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['region_search_controller'] = $app->share(
            function (Application $app) {
                return new RegionSearchController(
                    $app['region_elasticsearch_service'],
                    $app['region_name_map']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/suggestions/{input}', 'region_search_controller:suggest');

        return $controllers;
    }
}

<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class OrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['organizer_search_controller'] = $app->share(
            function (Application $app) {
                return new OrganizerSearchController(
                    $app['organizer_elasticsearch_service'],
                    $app['paged_collection_factory']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'organizer_search_controller:search');

        return $controllers;
    }
}

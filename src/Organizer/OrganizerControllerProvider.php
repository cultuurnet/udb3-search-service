<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\Symfony\Organizer\EditOrganizerRestController;
use CultuurNet\UDB3\Symfony\Organizer\ReadOrganizerRestController;
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
                    $app['organizer_elasticsearch_service']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'organizer_search_controller:search');

        return $controllers;
    }
}

<?php

namespace CultuurNet\UDB3\SearchService\ApiGuard;

use CultuurNet\UDB3\ApiGuard\Controller\ClearConsumerController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ApiGuardControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['auth.clear_consumer_controller'] = $app->share(
            function (Application $app) {
                return new ClearConsumerController(
                    $app[ApiGuardServiceProvider::REPOSITORY]
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->delete('/cache/{apiKey}', 'auth.clear_consumer_controller:clear');

        return $controllers;
    }
}

<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\SearchService\Http\AuthenticateRequest;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;

class RoutingServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Router::class,
    ];

    public function register()
    {
        $this->leagueContainer->add(
            Router::class,
            function () {
                $router = new Router();
                $strategy = (new ApplicationStrategy())->setContainer($this->getContainer());
                $router->setStrategy($strategy);

                if ($this->parameter('toggles.authentication.status') !== 'inactive') {
                    $router->middleware(
                        $this->getLeagueContainer()->get(AuthenticateRequest::class)
                    );
                }
                
                $router->get('/organizers/', OrganizerSearchController::class);
                $router->get('/offers/', ['offer_controller', '__invoke']);
                $router->get('/events/', ['event_controller', '__invoke']);
                $router->get('/places/', ['place_controller', '__invoke']);

                return $router;
            }
        );
    }
}

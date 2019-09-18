<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;

class RoutingServiceProvider extends AbstractServiceProvider
{
    protected $provides = [
        Router::class
    ];

    public function register()
    {
        $this->leagueContainer->add(
            Router::class,
            function () {
                $router = new Router();
                $strategy = (new ApplicationStrategy())->setContainer($this->getContainer());
                $router->setStrategy($strategy);

                $router->get('/organizers/', OrganizerSearchController::class);

                return $router;
            }
        );
    }
}

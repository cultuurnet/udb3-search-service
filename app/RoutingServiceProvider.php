<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\SearchService\Http\AuthenticateRequest;
use Fig\Http\Message\StatusCodeInterface;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Slim\Psr7\Response;
use Tuupola\Middleware\CorsMiddleware;

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

                $router->middleware(
                    new CorsMiddleware(
                        [
                            'origin' => ['*'],
                            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                            'headers.allow' => ['Authorization', 'X-Api-Key'],
                            'headers.expose' => [],
                            'credentials' => true,
                            'cache' => 0,
                        ]
                    )
                );

                // Register the OPTIONS method for every route to make the CORS middleware registered above work.
                $router->options('/{path:.*}', static function () {
                    return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
                });

                $router->get('/organizers', OrganizerSearchController::class);
                $router->get('/offers', ['offer_controller', '__invoke']);
                $router->get('/events', ['event_controller', '__invoke']);
                $router->get('/places', ['place_controller', '__invoke']);

                $router->get('/organizers/', OrganizerSearchController::class);
                $router->get('/offers/', ['offer_controller', '__invoke']);
                $router->get('/events/', ['event_controller', '__invoke']);
                $router->get('/places/', ['place_controller', '__invoke']);

                return $router;
            }
        );
    }
}

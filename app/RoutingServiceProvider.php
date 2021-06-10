<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\Authentication\AuthenticateRequest;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use Fig\Http\Message\StatusCodeInterface;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Slim\Psr7\Response;
use Tuupola\Middleware\CorsMiddleware;

final class RoutingServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Router::class,
        Consumer::class,
    ];

    public function register()
    {
        $this->leagueContainer->add(Consumer::class, new Consumer(null, null));

        $this->leagueContainer->add(
            Router::class,
            function () {
                $router = new Router();
                $strategy = (new ApplicationStrategy())->setContainer($this->getContainer());
                $router->setStrategy($strategy);

                if ($this->parameter('toggles.authentication.status') !== 'inactive') {
                    $oauthClient = new \CultureFeed_DefaultOAuthClient(
                        $this->parameter('uitid.consumer.key'),
                        $this->parameter('uitid.consumer.secret')
                    );
                    $oauthClient->setEndpoint($this->parameter('uitid.base_url'));

                    $router->middleware(
                        new AuthenticateRequest(
                            $this->getLeagueContainer(),
                            new \CultureFeed($oauthClient)
                        )
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

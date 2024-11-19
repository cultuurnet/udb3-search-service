<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultureFeed;
use CultureFeed_DefaultOAuthClient;
use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Http\Authentication\AuthenticateRequest;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\Search\Http\Authentication\Keycloak\KeycloakTokenGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Keycloak\KeycloakMetadataGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenFileRepository;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenProvider;
use CultuurNet\UDB3\Search\Http\Authentication\MetadataGenerator;
use CultuurNet\UDB3\Search\Http\DefaultQuery\InMemoryDefaultQueryRepository;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\SearchService\Error\LoggerFactory;
use CultuurNet\UDB3\SearchService\Error\LoggerName;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client;
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

    public function register(): void
    {
        $this->leagueContainer->add(Consumer::class, new Consumer(null, null));

        $this->leagueContainer->add(
            Router::class,
            function (): Router {
                $router = new Router();
                $strategy = (new ApplicationStrategy())->setContainer($this->getContainer());
                $router->setStrategy($strategy);

                if ($this->parameter('toggles.authentication.status') !== 'inactive') {
                    $oauthClient = new CultureFeed_DefaultOAuthClient(
                        $this->parameter('uitid.consumer.key'),
                        $this->parameter('uitid.consumer.secret')
                    );
                    $oauthClient->setEndpoint($this->parameter('uitid.base_url'));

                    $metadataGenerator = $this->getMetadataGenerator();

                    $pemFile = $this->parameter('keycloak.pem_file');
                    $authenticateRequest = new AuthenticateRequest(
                        $this->getLeagueContainer(),
                        new CultureFeed($oauthClient),
                        $this->getManagementTokenProvider(),
                        $metadataGenerator,
                        new InMemoryDefaultQueryRepository(
                            file_exists(__DIR__ . '/../default_queries.php') ? require __DIR__ . '/../default_queries.php' : []
                        ),
                        FileReader::read('file://' . __DIR__ . '/../' . $pemFile)
                    );

                    $logger = LoggerFactory::create($this->leagueContainer, LoggerName::forWeb());
                    $metadataGenerator->setLogger($logger);
                    $authenticateRequest->setLogger($logger);

                    $router->middleware($authenticateRequest);
                }

                $router->middleware(
                    new CorsMiddleware(
                        [
                            'origin' => ['*'],
                            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                            'headers.allow' => ['Authorization', 'X-Api-Key', 'X-Client-Id', 'X-Client-Properties', 'Content-Type'],
                            'headers.expose' => [],
                            'credentials' => true,
                            'cache' => 0,
                        ]
                    )
                );

                $optionsResponse = static fn (): Response => new Response(StatusCodeInterface::STATUS_NO_CONTENT);

                // Register the OPTIONS method for every route to make the CORS middleware registered above work.
                $router->get('/organizers', OrganizerSearchController::class);
                $router->options('/organizers', $optionsResponse);
                $router->get('/organizers/', OrganizerSearchController::class);
                $router->options('/organizers/', $optionsResponse);

                $router->get('/offers', ['offer_controller', '__invoke']);
                $router->options('/offers', $optionsResponse);
                $router->get('/offers/', ['offer_controller', '__invoke']);
                $router->options('/offers/', $optionsResponse);

                $router->get('/events', ['event_controller', '__invoke']);
                $router->options('/events', $optionsResponse);
                $router->get('/events/', ['event_controller', '__invoke']);
                $router->options('/events/', $optionsResponse);

                $router->get('/places', ['place_controller', '__invoke']);
                $router->options('/places', $optionsResponse);
                $router->get('/places/', ['place_controller', '__invoke']);
                $router->options('/places/', $optionsResponse);

                $router->get('/event', ['event_controller', '__invoke']);
                $router->options('/event', $optionsResponse);
                $router->get('/event/', ['event_controller', '__invoke']);
                $router->options('/event/', $optionsResponse);

                $router->get('/place', ['place_controller', '__invoke']);
                $router->options('/place', $optionsResponse);
                $router->get('/place/', ['place_controller', '__invoke']);
                $router->options('/place/', $optionsResponse);

                return $router;
            }
        );
    }

    private function getManagementTokenProvider(): ManagementTokenProvider
    {
        return new ManagementTokenProvider(
            new KeycloakTokenGenerator(
                new Client(),
                $this->parameter('keycloak.domain'),
                $this->parameter('keycloak.client_id'),
                $this->parameter('keycloak.client_secret'),
                $this->parameter('keycloak.domain') . '/api/v2/'
            ),
            new ManagementTokenFileRepository(__DIR__ . '/../cache/keycloak-management-token-cache.json'),
        );
    }

    private function getMetadataGenerator(): MetadataGenerator
    {
        return new KeycloakMetadataGenerator(
            new Client([
                'http_errors' => false,
            ]),
            $this->parameter('keycloak.domain'),
            $this->parameter('keycloak.realm'),
        );
    }
}

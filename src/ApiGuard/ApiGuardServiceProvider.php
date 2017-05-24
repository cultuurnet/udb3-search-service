<?php

namespace CultuurNet\UDB3\SearchService\ApiGuard;

use CultuurNet\UDB3\ApiGuard\ApiKey\AllowAnyAuthenticator;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ApiGuardServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['auth.api_key_reader'] = $app->share(
            function (Application $app) {
                $queryReader = new QueryParameterApiKeyReader('apiKey');
                $headerReader = new CustomHeaderApiKeyReader('X-Api-Key');

                return new CompositeApiKeyReader(
                    $queryReader,
                    $headerReader
                );
            }
        );

        $app['auth.api_key_authenticator'] = $app->share(
            function (Application $app) {
                return new AllowAnyAuthenticator();
            }
        );

        $app['auth.request_authenticator'] = $app->share(
            function (Application $app) {
                return new ApiKeyRequestAuthenticator(
                    $app['auth.api_key_reader'],
                    $app['auth.api_key_authenticator']
                );
            }
        );

        $app['auth.consumer_repository'] = $app->share(
            function (Application $app) {
                return new InMemoryConsumerRepository();
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}

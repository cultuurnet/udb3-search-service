<?php

namespace CultuurNet\UDB3\SearchService\Authentication;

use CultuurNet\UDB3\Search\Http\Authentication\ApiKey\AllowAnyAuthenticator;
use CultuurNet\UDB3\Search\Http\Authentication\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\Search\Http\Authentication\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\Search\Http\Authentication\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\Search\Http\Authentication\Request\ApiKeyRequestAuthenticator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthenticationServiceProvider implements ServiceProviderInterface
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
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}

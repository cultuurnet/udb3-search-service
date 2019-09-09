<?php

namespace CultuurNet\UDB3\SearchService\ApiGuard;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ApiGuardServiceProvider implements ServiceProviderInterface
{
    private const CONSUMER_REPOSITORY = 'auth.consumer_repository';

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
                return new CultureFeedApiKeyAuthenticator(
                    $app['culturefeed'],
                    $app['auth.consumer_repository'],
                    true
                );
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

        $app[self::CONSUMER_REPOSITORY] = $app->share(
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

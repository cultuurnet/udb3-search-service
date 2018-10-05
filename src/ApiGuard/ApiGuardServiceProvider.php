<?php

namespace CultuurNet\UDB3\SearchService\ApiGuard;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Doctrine\ConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use Doctrine\Common\Cache\PredisCache;
use Predis\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ApiGuardServiceProvider implements ServiceProviderInterface
{
    const REPOSITORY = 'auth.consumer_repository';

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
                    $app['auth.consumer_repository']
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

        $app['auth.redis_cache'] = $app->share(
            function (Application $app) {
                $parameters = $app['config']['cache']['redis'];

                $redisClient = new Client(
                    $parameters,
                    [
                        'prefix' => 'consumer_',
                    ]
                );

                $cache = new PredisCache($redisClient);

                return $cache;
            }
        );

        $app[self::REPOSITORY] = $app->share(
            function (Application $app) {
                return new ConsumerRepository(
                    $app['auth.redis_cache'],
                    $app['config']['cache']['lifetime'] ?? 86400
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

<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\ResultTransformingPagedCollectionFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PagedCollectionFactoryServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['paged_collection_logger'] = $app->share(
            function () {
                $logger = new Logger('paged_collection');

                $logger->pushHandler(
                    new StreamHandler(
                        __DIR__ . '/../log/paged_collection.log',
                        Logger::DEBUG
                    )
                );

                return $logger;
            }
        );

        $app['paged_collection_factory'] = $app->share(
            function (Application $app) {
                return new ResultTransformingPagedCollectionFactory(
                    $app['elasticsearch_result_transformer']
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

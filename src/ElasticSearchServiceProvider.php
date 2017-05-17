<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['elasticsearch_client'] = $app->share(
            function (Application $app) {
                return ClientBuilder::create()
                    ->setHosts(
                        [
                            $app['elasticsearch.host'],
                        ]
                    )
                    ->build();
            }
        );

        $app['elasticsearch_indexation_strategy'] = $app->share(
            function (Application $app) {
                return new MutableIndexationStrategy(
                    new SingleFileIndexationStrategy(
                        $app['elasticsearch_client'],
                        $app['logger.amqp.udb3_consumer']
                    )
                );
            }
        );

        $app['elasticsearch_query_string_factory'] = $app->share(
            function () {
                return new LuceneQueryStringFactory();
            }
        );

        $app['elasticsearch_transformer_logger'] = $app->share(
            function () {
                $logger = new Logger('elasticsearch.transformer');

                $logger->pushHandler(
                    new StreamHandler(
                        __DIR__ . '/../log/elasticsearch_transformer.log',
                        Logger::DEBUG
                    )
                );

                return $logger;
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

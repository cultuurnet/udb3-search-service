<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

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

        $app['elasticsearch_result_transformer'] = $app->share(
            function () {
                return new MinimalRequiredInfoJsonDocumentTransformer();
            }
        );

        $app->before(
            function (Request $request, Application $app) {
                // Check if the incoming request has an embed parameter.
                $embed = $request->query->get('embed', null);

                // Don't do anything if the embed parameter is null or an empty
                // string.
                if (is_null($embed) || (is_string($embed) && empty($embed))) {
                    return;
                }

                // Convert to a boolean.
                $embed = filter_var($embed, FILTER_VALIDATE_BOOLEAN);

                if (!$embed) {
                    // Don't do anything if embed is explicitly set to false.
                    return;
                }

                // If embed is true, replace the json document transformer used
                // by paged collection factory so it fetches the json-ld of all
                // results.
                $app['elasticsearch_result_transformer'] = $app->share(
                    function () {
                        return new JsonLdEmbeddingJsonDocumentTransformer();
                    }
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

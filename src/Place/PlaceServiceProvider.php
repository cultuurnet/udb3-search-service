<?php

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\Search\Place\PlaceSearchProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['place_search_projector'] = $app->share(
            function (Application $app) {
                $service = new TransformingJsonDocumentIndexService(
                    $app['http_client'],
                    $app['place_elasticsearch_transformer'],
                    $app['place_elasticsearch_repository']
                );
                $service->setLogger($app['logger.amqp.udb3_consumer']);

                return new PlaceSearchProjector($service);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

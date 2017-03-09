<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\JsonDocument\PassThroughJsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\TransformingJsonDocumentIndexService;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['organizer_search_projector'] = $app->share(
            function (Application $app) {
                return new OrganizerSearchProjector(
                    new TransformingJsonDocumentIndexService(
                        $app['http_client'],
                        $app['organizer_elasticsearch_transformer'],
                        $app['organizer_elasticsearch_repository']
                    )
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

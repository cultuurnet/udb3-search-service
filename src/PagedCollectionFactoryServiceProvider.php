<?php

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\JsonLdEmbeddingPagedCollectionFactory;
use CultuurNet\UDB3\Search\Http\PagedCollectionFactoryInterface;
use CultuurNet\UDB3\Search\Http\ResultSetMappingPagedCollectionFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class PagedCollectionFactoryServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['paged_collection_factory'] = $app->share(
            function (Application $app) {
                return new ResultSetMappingPagedCollectionFactory();
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

                // If embed is true, decorate the paged collection factory used
                // by offer controllers so it fetches the json-ld of all
                // results.
                $app->extend(
                    'paged_collection_factory',
                    function (
                        PagedCollectionFactoryInterface $pagedCollectionFactory,
                        Application $app
                    ) {
                        return new JsonLdEmbeddingPagedCollectionFactory(
                            $pagedCollectionFactory,
                            $app['http_client']
                        );
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

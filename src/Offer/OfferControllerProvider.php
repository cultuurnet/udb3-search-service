<?php

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistanceFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\EmbeddedJsonDocumentTransformer;
use CultuurNet\UDB3\Search\Http\JsonLdEmbeddingPagedCollectionFactory;
use CultuurNet\UDB3\Search\Http\NodeAwareFacetTreeNormalizer;
use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\Search\Http\PagedCollectionFactory;
use CultuurNet\UDB3\Search\Http\PagedCollectionFactoryInterface;
use CultuurNet\UDB3\Search\Http\ResultSetMappingPagedCollectionFactory;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;

class OfferControllerProvider implements ControllerProviderInterface
{
    /**
     * @var StringLiteral
     */
    private $regionIndexName;

    /**
     * @var StringLiteral
     */
    private $regionDocumentType;

    /**
     * @param StringLiteral $regionIndexName
     * @param StringLiteral $regionDocumentType
     */
    public function __construct(
        StringLiteral $regionIndexName,
        StringLiteral $regionDocumentType
    ) {
        $this->regionIndexName = $regionIndexName;
        $this->regionDocumentType = $regionDocumentType;
    }

    /**
     * @param Application $app
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $app['offer_search_controller_factory'] = $app->protect(
            function (OfferSearchServiceInterface $offerSearchService) use ($app) {
                return new OfferSearchController(
                    $offerSearchService,
                    $this->regionIndexName,
                    $this->regionDocumentType,
                    $app['elasticsearch_query_string_factory'],
                    new ElasticSearchDistanceFactory(),
                    new NodeAwareFacetTreeNormalizer(),
                    $app['offer_paged_collection_factory']
                );
            }
        );

        $app['offer_search_controller'] = $app->share(
            function (Application $app) {
                return $app['offer_search_controller_factory'](
                    $app['offer_elasticsearch_service']
                );
            }
        );

        $app['offer_paged_collection_factory'] = $app->share(
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
                    'offer_paged_collection_factory',
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

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'offer_search_controller:search');

        return $controllers;
    }
}

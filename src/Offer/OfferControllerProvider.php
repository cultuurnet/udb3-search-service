<?php

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistanceFactory;
use CultuurNet\UDB3\Search\Http\NodeAwareFacetTreeNormalizer;
use CultuurNet\UDB3\Search\Http\Offer\RequestParser\CompositeOfferRequestParser;
use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
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
                    $app['offer_elasticsearch_query_builder'],
                    new CompositeOfferRequestParser(),
                    $offerSearchService,
                    $this->regionIndexName,
                    $this->regionDocumentType,
                    $app['elasticsearch_query_string_factory'],
                    new ElasticSearchDistanceFactory(),
                    new NodeAwareFacetTreeNormalizer(),
                    $app['paged_collection_factory']
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

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'offer_search_controller:search');

        return $controllers;
    }
}

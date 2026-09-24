<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Offer;

use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\CompositeAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\LabelsAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NodeMapAggregationTransformer;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceFactory;
use CultuurNet\UDB3\Search\Taxonomy\TaxonomyApiClient;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Elasticsearch\Client;

final class OfferSearchServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'offer_controller',
        OfferSearchControllerFactory::class,
        OfferSearchServiceFactory::class,
    ];

    public function register(): void
    {
        $this->add(
            'offer_controller',
            function (): OfferSearchController {
                /** @var OfferSearchControllerFactory $offerControllerFactory */
                $offerControllerFactory = $this->get(OfferSearchControllerFactory::class);

                return $offerControllerFactory->createFor(
                    $this->parameter('elasticsearch.offer.read_index'),
                    $this->parameter('elasticsearch.offer.document_type')
                );
            }
        );

        $this->add(
            OfferSearchControllerFactory::class,
            fn (): OfferSearchControllerFactory => new OfferSearchControllerFactory(
                $this->parameter('elasticsearch.aggregation_size'),
                $this->parameter('elasticsearch.region.read_index'),
                $this->parameter('elasticsearch.region.document_type'),
                $this->get(OfferSearchServiceFactory::class),
                $this->get(Consumer::class),
            )
        );

        $this->add(
            OfferSearchServiceFactory::class,
            function (): OfferSearchServiceFactory {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $this->container->get(TaxonomyApiClient::class);
                $transformer = new CompositeAggregationTransformer();
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::Regions,
                        $this->parameter('facet_mapping_regions')
                    )
                );
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::Themes,
                        $taxonomyApiClient->getThemes()
                    )
                );
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::Types,
                        $taxonomyApiClient->getTypes()
                    )
                );
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::Facilities,
                        $taxonomyApiClient->getFacilities()
                    )
                );
                $transformer->register(
                    new LabelsAggregationTransformer(
                        FacetName::Labels
                    )
                );

                return new OfferSearchServiceFactory(
                    $this->get(Client::class),
                    $transformer
                );
            }
        );
    }
}

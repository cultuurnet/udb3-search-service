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
            function (): OfferSearchControllerFactory {
                $factory = new OfferSearchControllerFactory(
                    $this->parameter('elasticsearch.aggregation_size'),
                    $this->parameter('elasticsearch.region.read_index'),
                    $this->parameter('elasticsearch.region.document_type'),
                    $this->get(OfferSearchServiceFactory::class),
                    $this->get(Consumer::class)
                );
                if ($this->usesElasticSearch5()) {
                    $factory->enableElasticSearch5CompatibilityMode();
                }
                return $factory;
            }
        );

        $this->add(
            OfferSearchServiceFactory::class,
            function (): OfferSearchServiceFactory {
                $transformer = new CompositeAggregationTransformer();
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::regions(),
                        $this->parameter('facet_mapping_regions')
                    )
                );
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::themes(),
                        $this->container->get(TaxonomyApiClient::class)->getThemes()
                    )
                );
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::types(),
                        $this->container->get(TaxonomyApiClient::class)->getTypes()
                    )
                );
                $transformer->register(
                    new NodeMapAggregationTransformer(
                        FacetName::facilities(),
                        $this->container->get(TaxonomyApiClient::class)->getFacilities()
                    )
                );
                $transformer->register(
                    new LabelsAggregationTransformer(
                        FacetName::labels()
                    )
                );

                $factory = new OfferSearchServiceFactory(
                    $this->get(Client::class),
                    $transformer
                );

                if ($this->usesElasticSearch5()) {
                    $factory->enableElasticSearch5CompatibilityMode();
                }

                return $factory;
            }
        );
    }
}

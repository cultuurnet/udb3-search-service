<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NodeMapAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDistanceFactory;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerSearchService;
use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\Search\Http\NodeAwareFacetTreeNormalizer;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\ContributorsRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\DistanceOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\GeoBoundsOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\IdRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\Search\Http\Parameters\GeoBoundsParametersFactory;
use CultuurNet\UDB3\Search\Http\Parameters\GeoDistanceParametersFactory;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Elastic\Elasticsearch\ClientInterface;

final class OrganizerSearchServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        OrganizerSearchController::class,
        'organizer_search_projector',
        'event_bus_subscribers',
    ];

    public function register(): void
    {
        $this->add(
            OrganizerSearchController::class,
            function (): OrganizerSearchController {
                $requestParser = (new CompositeOrganizerRequestParser())
                    ->withParser(new IdRequestParser())
                    ->withParser(new DistanceOrganizerRequestParser(
                        new GeoDistanceParametersFactory(new ElasticSearchDistanceFactory())
                    ))
                    ->withParser(new GeoBoundsOrganizerRequestParser(
                        new GeoBoundsParametersFactory()
                    ))
                    ->withParser(new WorkflowStatusOrganizerRequestParser())
                    ->withParser(new ContributorsRequestParser())
                    ->withParser(new SortByOrganizerRequestParser());

                return new OrganizerSearchController(
                    new ElasticSearchOrganizerQueryBuilder(
                        $this->parameter('elasticsearch.aggregation_size')
                    ),
                    new ElasticSearchOrganizerSearchService(
                        $this->get(ClientInterface::class),
                        $this->parameter('elasticsearch.organizer.read_index'),
                        $this->parameter('elasticsearch.organizer.document_type'),
                        new ElasticSearchPagedResultSetFactory(
                            new NodeMapAggregationTransformer(
                                FacetName::regions(),
                                $this->parameter('facet_mapping_regions')
                            )
                        )
                    ),
                    $this->parameter('elasticsearch.region.read_index'),
                    $this->parameter('elasticsearch.region.document_type'),
                    $requestParser,
                    new LuceneQueryStringFactory(),
                    new NodeAwareFacetTreeNormalizer(),
                    $this->get(Consumer::class)
                );
            }
        );
    }
}

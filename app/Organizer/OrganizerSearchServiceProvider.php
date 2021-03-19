<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\NullAggregationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerSearchService;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\CompositeOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\SortByOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\Organizer\RequestParser\WorkflowStatusOrganizerRequestParser;
use CultuurNet\UDB3\Search\Http\OrganizerSearchController;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Elasticsearch\Client;

final class OrganizerSearchServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        OrganizerSearchController::class,
        'organizer_search_projector',
        'event_bus_subscribers',
    ];

    public function register()
    {
        $this->add(
            OrganizerSearchController::class,
            function () {
                $requestParser = (new CompositeOrganizerRequestParser())
                    ->withParser(new WorkflowStatusOrganizerRequestParser())
                    ->withParser(new SortByOrganizerRequestParser());

                return new OrganizerSearchController(
                    $this->get(ApiKeyReaderInterface::class),
                    new ElasticSearchOrganizerQueryBuilder(),
                    new ElasticSearchOrganizerSearchService(
                        $this->get(Client::class),
                        $this->parameter('elasticsearch.organizer.read_index'),
                        $this->parameter('elasticsearch.organizer.document_type'),
                        new ElasticSearchPagedResultSetFactory(
                            new NullAggregationTransformer()
                        )
                    ),
                    $requestParser,
                    new LuceneQueryStringFactory()
                );
            }
        );
    }
}

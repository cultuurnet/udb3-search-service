<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\CalendarSummary;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\Http\CalendarSummary\CalendarSummaryController;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;

final class CalendarSummaryServiceProvider extends BaseServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === CalendarSummaryController::class;
    }

    public function register(): void
    {
        $this->add(
            CalendarSummaryController::class,
            fn (): CalendarSummaryController => new CalendarSummaryController(
                $this->get(ElasticSearchClient::class),
                $this->parameter('elasticsearch.event.read_index'),
                $this->parameter('elasticsearch.place.read_index')
            )
        );
    }
}

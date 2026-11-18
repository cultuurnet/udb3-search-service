<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\LocationSuggestions;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\Http\LocationSuggestionsController;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;

final class LocationSuggestionsServiceProvider extends BaseServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === LocationSuggestionsController::class;
    }

    public function register(): void
    {
        $this->add(
            LocationSuggestionsController::class,
            fn (): LocationSuggestionsController => new LocationSuggestionsController(
                $this->get(ElasticSearchClient::class),
                $this->parameter('elasticsearch.location_suggestions_index.read_alias')
            )
        );
    }
}

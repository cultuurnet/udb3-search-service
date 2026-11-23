<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\LocationSuggestions\SearchText;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Psr\Http\Message\ResponseInterface;

final readonly class LocationSuggestionsController
{
    private const NUMBER_OF_SUGGESTIONS = 10;

    public function __construct(
        private ElasticSearchClient $client,
        private string $indexName,
    ) {
    }

    public function __invoke(ApiRequestInterface $request): ResponseInterface
    {
        $q = $request->getQueryParam('q', '');
        if (!is_string($q)) {
            throw new UnsupportedParameterValue('The "q" parameter must be a single text value.');
        }

        $searchText = new SearchText($q);

        // Every typed word must match. The last one may be the beginning of a word, because the user is still typing it.
        // A number at the end is the exception: as a beginning, "lentedreef 2" would also match 12, 20 and postcode 2360.
        $matchType = $searchText->endsWithNumber() ? 'match' : 'match_bool_prefix';

        // Without a number the user means the street, so the house numbers on it would only crowd it out.
        $types = $searchText->containsNumber() ? ['street', 'address'] : ['street'];

        $response = $this->client->search([
            'index' => $this->indexName,
            'body' => [
                'size' => self::NUMBER_OF_SUGGESTIONS,
                'query' => [
                    'bool' => [
                        'must' => [[$matchType => ['search_text' => ['query' => $searchText->toString(), 'operator' => 'and']]]],
                        'filter' => [['terms' => ['type' => $types]]],
                    ],
                ],
            ],
        ]);

        $suggestions = array_map(
            static fn (array $hit): array => [
                'type' => $hit['_source']['type'],
                'label' => $hit['_source']['label'],
                // Named like the geo of places and events elsewhere in the API.
                'geo' => [
                    'latitude' => $hit['_source']['location']['lat'],
                    'longitude' => $hit['_source']['location']['lon'],
                ],
            ],
            $response['hits']['hits']
        );

        return ResponseFactory::jsonLd(['member' => $suggestions]);
    }
}

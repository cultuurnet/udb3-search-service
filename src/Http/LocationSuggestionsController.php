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
    private const EXACT_NAME_BOOST = 10;

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
        // A number at the end is the exception: as a beginning, "lentedreef 2" would also match 12, 20 and postal code 2360.
        // Only numbers is a postal code being typed, so there it is a beginning again: 30 finds 3000 Leuven and 3010 Kessel-Lo.
        $matchType = $searchText->endsWithNumber() && !$searchText->isOnlyNumbers() ? 'match' : 'match_bool_prefix';

        // Without a number the user means the street, so the house numbers on it would only crowd it out.
        // A municipality has no number, so with one typed it can never match. A sub-municipality has its postal code.
        // Only numbers is a postal code, and the user means the area: not its streets, and a house number on its own means nothing.
        $types = match (true) {
            !$searchText->containsNumber() => ['street', 'municipality', 'submunicipality'],
            $searchText->isOnlyNumbers() => ['submunicipality'],
            default => ['street', 'submunicipality', 'address'],
        };

        $response = $this->client->search([
            'index' => $this->indexName,
            // The alias can span more than one index. Elasticsearch scores how rare a word is per index,
            // so without this the same word would score differently depending on the index it is in.
            'search_type' => 'dfs_query_then_fetch',
            'body' => [
                'size' => self::NUMBER_OF_SUGGESTIONS,
                'query' => [
                    'function_score' => [
                        'query' => [
                            'bool' => [
                                'must' => [[$matchType => ['search_text' => ['query' => $searchText->toString(), 'operator' => 'and']]]],
                                // A street named exactly what was typed comes first, so bisschoppenhof shows the Bisschoppenhof above every Bisschoppenhoflaan.
                                // Scored like any match, so the boost is multiplied by how rare the name is. Even Kerkstraat ends up far above
                                // the prefix matches, which all score the same.
                                'should' => [['match' => ['name' => ['query' => $searchText->toString(), 'boost' => self::EXACT_NAME_BOOST]]]],
                                'filter' => [['terms' => ['type' => $types]]],
                            ],
                        ],
                        // A street with more addresses comes first. The logarithm keeps a big city from burying a small-town match.
                        // Addresses have no count, so they all get the factor of a street with one address.
                        'field_value_factor' => ['field' => 'address_count', 'modifier' => 'log1p', 'factor' => 0.5, 'missing' => 1],
                        'boost_mode' => 'multiply',
                    ],
                ],
            ],
        ]);

        $suggestions = array_map(
            static fn (array $hit): array => [
                // Both are an area with a region, so clients don't need to tell them apart. The label does: Leuven and Leuven (3000).
                'type' => $hit['_source']['type'] === 'submunicipality' ? 'municipality' : $hit['_source']['type'],
                'label' => $hit['_source']['label'],
                // No coordinates of their own: a client searches with the searchFilter, and for an area an average point would be the wrong filter.
                'searchFilter' => self::searchFilter($hit['_source']),
            ],
            $response['hits']['hits']
        );

        return ResponseFactory::jsonLd(['member' => $suggestions]);
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private static function searchFilter(array $source): array
    {
        // A municipality is an area, so Search API 3 filters on its region. Everything else is a point.
        if (in_array($source['type'], ['municipality', 'submunicipality'], true)) {
            return ['regions' => $source['regions']];
        }

        return ['coordinates' => $source['location']['lat'] . ',' . $source['location']['lon']];
    }
}

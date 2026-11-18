<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Psr\Http\Message\ResponseInterface;

final class LocationSuggestionsController
{
    private const NUMBER_OF_SUGGESTIONS = 10;

    // Nobody types an address this long, and a longer text could exceed the Elasticsearch clause limit.
    private const MAX_TYPED_TEXT_LENGTH = 120;

    public function __construct(
        private readonly ElasticSearchClient $client,
        private readonly string $indexName,
    ) {
    }

    public function __invoke(ApiRequestInterface $request): ResponseInterface
    {
        $typedText = $request->getQueryParam('q', '');
        if (!is_string($typedText)) {
            throw new UnsupportedParameterValue('The "q" parameter must be a single text value.');
        }

        $typedText = trim($typedText);
        if ($typedText === '') {
            throw new MissingParameter('Required "q" parameter is missing.');
        }
        if (mb_strlen($typedText) > self::MAX_TYPED_TEXT_LENGTH) {
            throw new UnsupportedParameterValue(
                sprintf('The "q" parameter can be at most %d characters long.', self::MAX_TYPED_TEXT_LENGTH)
            );
        }

        $response = $this->client->search([
            'index' => $this->indexName,
            'body' => [
                'size' => self::NUMBER_OF_SUGGESTIONS,
                'query' => [
                    // Every typed word must match. The last one may be the beginning of a word, because the user is still typing it.
                    'match_bool_prefix' => ['search_text' => ['query' => $typedText, 'operator' => 'and']],
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

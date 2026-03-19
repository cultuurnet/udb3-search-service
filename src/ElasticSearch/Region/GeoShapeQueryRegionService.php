<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Region;

use RuntimeException;
use CultuurNet\UDB3\Search\Region\RegionId;
use Elasticsearch\Client;

final class GeoShapeQueryRegionService implements RegionServiceInterface
{
    /**
     * Amount of (matching) regions per page.
     */
    public const PAGE_SIZE = 10;

    private Client $client;

    private string $indexName;

    private int $elasticsearchVersion;

    public function __construct(
        Client $elasticSearchClient,
        string $geoShapesIndexName,
        int $elasticsearchVersion = 5
    ) {
        $this->client = $elasticSearchClient;
        $this->indexName = $geoShapesIndexName;
        $this->elasticsearchVersion = $elasticsearchVersion;
    }

    /**
     * @inheritdoc
     */
    public function getRegionIds(array $geoShape): array
    {
        $regionIds = [];

        $query = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match_all' => (object) [],
                    ],
                    'filter' => [
                        'geo_shape' => [
                            'location' => [
                                'shape' => $geoShape,
                                'relation' => 'contains',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $pageNumber = 0;
        $processedHits = 0;

        do {
            $response = $this->client->search(
                [
                    'index' => $this->indexName,
                    'body' => $query,
                    'size' => self::PAGE_SIZE,
                    'from' => self::PAGE_SIZE * $pageNumber,
                ]
            );

            if (!isset($response['hits']) || !isset($response['hits']['total']) || !isset($response['hits']['hits'])) {
                throw new RuntimeException(
                    'Got invalid response from ElasticSearch when trying to find matching regions.'
                );
            }

            $total = is_array($response['hits']['total'])
                ? $response['hits']['total']['value']
                : $response['hits']['total'];

            foreach ($response['hits']['hits'] as $hit) {
                if ($this->elasticsearchVersion !== 8 && $hit['_type'] !== 'region') {
                    $processedHits++;
                    continue;
                }
                $regionIds[] = new RegionId($hit['_id']);
                $processedHits++;
            }

            $pageNumber++;
        } while ($total > $processedHits);

        return $regionIds;
    }
}

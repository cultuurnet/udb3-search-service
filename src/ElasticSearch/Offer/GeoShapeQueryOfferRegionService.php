<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Region\RegionId;
use Elasticsearch\Client;

final class GeoShapeQueryOfferRegionService implements OfferRegionServiceInterface
{
    /**
     * Amount of (matching) regions per page.
     */
    public const PAGE_SIZE = 10;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $indexName;


    public function __construct(
        Client $elasticSearchClient,
        string $geoShapesIndexName
    ) {
        $this->client = $elasticSearchClient;
        $this->indexName = $geoShapesIndexName;
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
                throw new \RuntimeException(
                    'Got invalid response from ElasticSearch when trying to find matching regions.'
                );
            }

            $total = $response['hits']['total'];

            foreach ($response['hits']['hits'] as $hit) {
                if ($hit['_type'] == 'region') {
                    $regionIds[] = new RegionId($hit['_id']);
                }

                $processedHits++;
            }

            $pageNumber++;
        } while ($total > $processedHits);

        return $regionIds;
    }
}

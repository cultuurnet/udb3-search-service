<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class GeoShapeQueryOfferRegionService implements OfferRegionServiceInterface
{
    /**
     * Amount of (matching) regions per page.
     */
    const PAGE_SIZE = 10;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var StringLiteral
     */
    private $indexName;

    /**
     * @param Client $elasticSearchClient
     * @param StringLiteral $geoShapesIndexName
     */
    public function __construct(
        Client $elasticSearchClient,
        StringLiteral $geoShapesIndexName
    ) {
        $this->client = $elasticSearchClient;
        $this->indexName = $geoShapesIndexName;
    }

    /**
     * @inheritdoc
     */
    public function getRegionIds(OfferType $offerType, JsonDocument $jsonDocument)
    {
        $regionIds = [];

        $id = $jsonDocument->getId();
        $documentSource = json_decode($jsonDocument->getRawBody(), true);
        $documentType = strtolower($offerType->toNative());

        if (!isset($documentSource['geo'])) {
            return [];
        }

        $query = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match_all' => (object) [],
                    ],
                    'filter' => [
                        'geo_shape' => [
                            'location' => [
                                'shape' => $documentSource['geo'],
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
                    'index' => $this->indexName->toNative(),
                    'body' => $query,
                    'size' => self::PAGE_SIZE,
                    'from' => self::PAGE_SIZE * $pageNumber,
                ]
            );

            if (!isset($response['hits']) || !isset($response['hits']['total']) || !isset($response['hits']['hits'])) {
                throw new \RuntimeException(
                    "Got invalid response from ElasticSearch when trying to find regions for $documentType $id."
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

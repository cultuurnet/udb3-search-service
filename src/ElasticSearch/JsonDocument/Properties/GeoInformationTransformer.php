<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\OfferType;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use Rhumsaa\Uuid\Uuid;

final class GeoInformationTransformer implements JsonTransformer
{
    /**
     * @var OfferType
     */
    private $offerType;

    /**
     * @var OfferRegionServiceInterface
     */
    private $offerRegionService;

    public function __construct(OfferType $offerType, OfferRegionServiceInterface $offerRegionService)
    {
        $this->offerType = $offerType;
        $this->offerRegionService = $offerRegionService;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['geo'])) {
            return $draft;
        }

        $draft['geo']['type'] = 'Point';

        // Important! In GeoJSON, and therefore Elasticsearch, the correct coordinate order is longitude, latitude
        // (X, Y) within coordinate arrays. This differs from many Geospatial APIs (e.g., Google Maps) that
        // generally use the colloquial latitude, longitude (Y, X).
        // @see https://www.elastic.co/guide/en/elasticsearch/reference/current/geo-shape.html#input-structure
        $draft['geo']['coordinates'] = [
            $from['geo']['longitude'],
            $from['geo']['latitude'],
        ];

        // We need to duplicate the geo coordinates in an extra field to enable geo distance queries.
        // ElasticSearch has 2 formats for geo coordinates, one datatype indexed to facilitate geoshape queries,
        // and another datatype indexed to facilitate geo distance queries.
        $draft['geo_point'] = [
            'lat' => $from['geo']['latitude'],
            'lon' => $from['geo']['longitude'],
        ];

        $regions = $this->getRegionIds($draft);
        if ($regions) {
            $draft['regions'] = $regions;
        }

        return $draft;
    }

    private function getRegionIds(array $json): array
    {
        $jsonDocument = (new JsonDocument(Uuid::NIL))
            ->withBody($json);

        $regionIds = $this->offerRegionService->getRegionIds(
            $this->offerType,
            $jsonDocument
        );

        if (empty($regionIds)) {
            return [];
        }

        return array_map(
            function (RegionId $regionId) {
                return $regionId->toNative();
            },
            $regionIds
        );
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\Offer\OfferType;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use Psr\Log\LoggerInterface;

abstract class AbstractOfferJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    /**
     * @var IdUrlParserInterface
     */
    protected $idUrlParser;

    /**
     * @var OfferRegionServiceInterface
     */
    protected $offerRegionService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param IdUrlParserInterface $idUrlParser
     * @param OfferRegionServiceInterface $offerRegionService
     * @param LoggerInterface $logger
     */
    public function __construct(
        IdUrlParserInterface $idUrlParser,
        OfferRegionServiceInterface $offerRegionService,
        LoggerInterface $logger
    ) {
        $this->idUrlParser = $idUrlParser;
        $this->offerRegionService = $offerRegionService;
        $this->logger = $logger;
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    protected function copyMediaObjectsCount(\stdClass $from, \stdClass $to)
    {
        $mediaObjectsCount = isset($from->mediaObject) ? count($from->mediaObject) : 0;
        $to->mediaObjectsCount = $mediaObjectsCount;
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    protected function copyGeoInformation(\stdClass $from, \stdClass $to)
    {
        if (isset($from->geo)) {
            $to->geo = new \stdClass();
            $to->geo->type = 'Point';

            // Important! In GeoJSON, and therefore Elasticsearch, the correct coordinate order is longitude, latitude
            // (X, Y) within coordinate arrays. This differs from many Geospatial APIs (e.g., Google Maps) that
            // generally use the colloquial latitude, longitude (Y, X).
            // @see https://www.elastic.co/guide/en/elasticsearch/reference/current/geo-shape.html#input-structure
            $to->geo->coordinates = [
                $from->geo->longitude,
                $from->geo->latitude,
            ];

            // We need to duplicate the geo coordinates in an extra field to enable geo distance queries.
            // ElasticSearch has 2 formats for geo coordinates, one datatype indexed to facilitate geoshape queries,
            // and another datatype indexed to facilitate geo distance queries.
            $to->geo_point = [
                'lat' => $from->geo->latitude,
                'lon' => $from->geo->longitude,
            ];
        }
    }

    /**
     * @param OfferType $offerType
     * @param JsonDocument $jsonDocument
     * @return string[]
     */
    protected function getRegionIds(
        OfferType $offerType,
        JsonDocument $jsonDocument
    ) {
        $regionIds = $this->offerRegionService->getRegionIds(
            $offerType,
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

    protected function logMissingExpectedField(string $fieldName): void
    {
        $this->logger->warning("Missing expected field '{$fieldName}'.");
    }
}

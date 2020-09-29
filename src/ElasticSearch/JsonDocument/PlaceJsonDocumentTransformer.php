<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonPsrLogger;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\Offer\OfferType;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Psr\Log\LoggerInterface;

/**
 * Converts Place JSON-LD to a format more ideal for searching.
 * Should be used when indexing Places.
 */
class PlaceJsonDocumentTransformer extends AbstractOfferJsonDocumentTransformer
{
    /**
     * @var PlaceTransformer
     */
    private $copyJsonPlace;

    public function __construct(
        IdUrlParserInterface $idUrlParser,
        OfferRegionServiceInterface $offerRegionService,
        LoggerInterface $logger
    ) {
        parent::__construct($idUrlParser, $offerRegionService, $logger);

        $this->copyJsonPlace = new PlaceTransformer(
            new CopyJsonPsrLogger($this->logger),
            $this->idUrlParser
        );
    }

    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $id = $jsonDocument->getId();
        $body = $jsonDocument->getBody();
        $newBody = new \stdClass();

        $this->logger->debug("Transforming place {$id} for indexation.");

        $this->copyJsonPlace->copy($body, $newBody);

        $this->copyCalendarType($body, $newBody);
        $this->copyDateRange($body, $newBody);

        $this->copyDescription($body, $newBody);

        $this->copyMainLanguage($body, $newBody);

        $this->copyLabelsForFreeTextSearch($body, $newBody);
        $this->copyTermsForFreeTextSearch($body, $newBody);
        $this->copyTermsForAggregations($body, $newBody);

        $this->copyPriceInfo($body, $newBody);
        $this->copyAudienceType($body, $newBody);

        $this->copyMediaObjectsCount($body, $newBody);

        $this->copyGeoInformation($body, $newBody);

        $regionIds = $this->getRegionIds(
            OfferType::PLACE(),
            $jsonDocument->withBody($newBody)
        );

        if (!empty($regionIds)) {
            $newBody->regions = $regionIds;
        }

        $this->logger->debug("Transformation of place {$id} finished.");

        return $jsonDocument->withBody($newBody);
    }
}

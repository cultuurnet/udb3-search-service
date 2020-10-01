<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
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
    private $placeTransformer;

    public function __construct(
        IdUrlParserInterface $idUrlParser,
        OfferRegionServiceInterface $offerRegionService,
        LoggerInterface $logger
    ) {
        parent::__construct($idUrlParser, $offerRegionService, $logger);

        $this->placeTransformer = new PlaceTransformer(
            new JsonTransformerPsrLogger($this->logger),
            $this->idUrlParser
        );
    }

    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $id = $jsonDocument->getId();

        $this->logger->debug("Transforming place {$id} for indexation.");

        // @todo refactor copy methods to transformer classes and remove workaround to make newBody an stdClass
        $from = json_decode($jsonDocument->getRawBody(), true);
        $to = [];
        $body = $jsonDocument->getBody();
        $newBody = json_decode(
            json_encode(
                $this->placeTransformer->transform($from, $to)
            )
        );

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

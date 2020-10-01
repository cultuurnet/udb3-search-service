<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\Offer\OfferType;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Psr\Log\LoggerInterface;

/**
 * Converts Place JSON-LD to a format more ideal for searching.
 * Should be used when indexing Places.
 */
class PlaceJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    /**
     * @var PlaceTransformer
     */
    private $placeTransformer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        IdUrlParserInterface $idUrlParser,
        OfferRegionServiceInterface $offerRegionService,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        $this->placeTransformer = new PlaceTransformer(
            new JsonTransformerPsrLogger($this->logger),
            $idUrlParser,
            $offerRegionService
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

        $this->logger->debug("Transformation of place {$id} finished.");

        return $jsonDocument->withBody($newBody);
    }
}

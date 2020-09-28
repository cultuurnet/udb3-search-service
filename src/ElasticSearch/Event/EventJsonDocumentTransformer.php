<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Event;

use CultuurNet\UDB3\Search\Offer\OfferType;
use CultuurNet\UDB3\Search\Event\EventJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonPsrLogger;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\AbstractOfferJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Converts Event JSON-LD to a format more ideal for searching.
 * Should be used when indexing Events.
 */
class EventJsonDocumentTransformer extends AbstractOfferJsonDocumentTransformer
{
    /**
     * @var CopyJsonEvent
     */
    private $copyJsonEvent;

    public function __construct(
        IdUrlParserInterface $idUrlParser,
        OfferRegionServiceInterface $offerRegionService,
        LoggerInterface $logger
    ) {
        $languageAnalyzer = new EventJsonDocumentLanguageAnalyzer();

        parent::__construct($idUrlParser, $offerRegionService, $logger, $languageAnalyzer);

        $this->copyJsonEvent = new CopyJsonEvent(
            new CopyJsonPsrLogger($this->logger),
            $this->idUrlParser
        );
    }

    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $id = $jsonDocument->getId();
        $body = $jsonDocument->getBody();
        $newBody = new \stdClass();

        $this->logger->debug("Transforming event {$id} for indexation.");

        $this->copyJsonEvent->copy($body, $newBody);

        $this->copyCalendarType($body, $newBody);
        $this->copyDateRange($body, $newBody);

        $this->copyDescription($body, $newBody);

        $this->copyMainLanguage($body, $newBody);

        $this->copyTermsForFreeTextSearch($body, $newBody);
        $this->copyTermsForAggregations($body, $newBody);
        $this->copyLabelsForFreeTextSearch($body, $newBody);

        $this->copyPerformer($body, $newBody);
        $this->copyPriceInfo($body, $newBody);
        $this->copyAudienceType($body, $newBody);

        $this->copyMediaObjectsCount($body, $newBody);

        if (isset($body->location)) {
            $this->copyGeoInformation($body->location, $newBody);

            $regionIds = $this->getRegionIds(
                OfferType::EVENT(),
                $jsonDocument->withBody($newBody)
            );

            if (!empty($regionIds)) {
                $newBody->regions = $regionIds;
            }
        }

        $this->logger->debug("Transformation of event {$id} finished.");

        return $jsonDocument->withBody($newBody);
    }

    private function copyPerformer(\stdClass $from, \stdClass $to): void
    {
        if (isset($from->performer) && is_array($from->performer)) {
            $to->performer_free_text = array_map(
                function ($performer) {
                    // Don't copy all properties, just those we're interested
                    // in.
                    $newPerformer = new \stdClass();
                    $newPerformer->name = $performer->name;
                    return $newPerformer;
                },
                $from->performer
            );
        }
    }
}

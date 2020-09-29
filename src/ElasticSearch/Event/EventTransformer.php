<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Event;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\RelatedProductionTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\OfferTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\RelatedLocationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class EventTransformer implements CopyJsonInterface
{
    /**
     * @var OfferTransformer
     */
    private $copyJsonOffer;

    /**
     * @var RelatedLocationTransformer
     */
    private $copyJsonRelatedLocation;

    /**
     * @var RelatedProductionTransformer
     */
    private $copyRelatedProduction;

    /**
     * @var LanguagesTransformer
     */
    private $copyJsonLanguages;

    /**
     * @param CopyJsonLoggerInterface $logger
     * @param IdUrlParserInterface $idUrlParser
     */
    public function __construct(
        CopyJsonLoggerInterface $logger,
        IdUrlParserInterface $idUrlParser
    ) {
        $this->copyJsonOffer = new OfferTransformer(
            $logger,
            $idUrlParser,
            FallbackType::EVENT()
        );

        $this->copyJsonRelatedLocation = new RelatedLocationTransformer(
            $logger,
            $idUrlParser,
            FallbackType::PLACE()
        );

        $this->copyRelatedProduction = new RelatedProductionTransformer();

        $this->copyJsonLanguages = new LanguagesTransformer($logger);
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $this->copyJsonOffer->copy($from, $to);

        $this->copyJsonRelatedLocation->copy($from, $to);

        $this->copyRelatedProduction->copy($from, $to);

        $this->copyJsonLanguages->copy($from, $to);
    }
}

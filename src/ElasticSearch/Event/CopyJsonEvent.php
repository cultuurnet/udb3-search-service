<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Event;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonLanguages;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyRelatedProduction;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonOffer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonRelatedLocation;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class CopyJsonEvent implements CopyJsonInterface
{
    /**
     * @var CopyJsonOffer
     */
    private $copyJsonOffer;

    /**
     * @var CopyJsonRelatedLocation
     */
    private $copyJsonRelatedLocation;

    /**
     * @var CopyRelatedProduction
     */
    private $copyRelatedProduction;

    /**
     * @var CopyJsonLanguages
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
        $this->copyJsonOffer = new CopyJsonOffer(
            $logger,
            $idUrlParser,
            FallbackType::EVENT()
        );

        $this->copyJsonRelatedLocation = new CopyJsonRelatedLocation(
            $logger,
            $idUrlParser,
            FallbackType::PLACE()
        );

        $this->copyRelatedProduction = new CopyRelatedProduction();

        $this->copyJsonLanguages = new CopyJsonLanguages($logger);
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
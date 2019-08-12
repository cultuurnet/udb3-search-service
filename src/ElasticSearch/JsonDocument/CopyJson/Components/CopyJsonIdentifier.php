<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class CopyJsonIdentifier implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    /**
     * @var IdUrlParserInterface
     */
    private $idUrlParser;

    /**
     * @var string
     */
    private $fallbackType;

    /**
     * CopyJsonName constructor.
     * @param CopyJsonLoggerInterface $logger
     * @param IdUrlParserInterface $idUrlParser
     * @param $fallbackType
     */
    public function __construct(
        CopyJsonLoggerInterface $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        $this->logger = $logger;
        $this->idUrlParser = $idUrlParser;
        $this->fallbackType = $fallbackType;
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (isset($from->{"@id"})) {
            $to->{"@id"} = $from->{"@id"};
        } else {
            $this->logger->logMissingExpectedField("@id");
        }

        $to->{"@type"} = isset($from->{"@type"}) ? $from->{"@type"} :
            $this->fallbackType->toNative();

        // Not included in the if statement above because it should be under
        // @type in the JSON. No else statement because we don't want to log a
        // missing @id twice.
        if (isset($from->{"@id"})) {
            $to->id = $this->idUrlParser->getIdFromUrl($from->{"@id"});
        }
    }
}

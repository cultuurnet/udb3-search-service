<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonAddress;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonIdentifier;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonLabels;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonName;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonTerms;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class CopyJsonRelatedLocation implements CopyJsonInterface
{
    /**
     * @var IdUrlParserInterface
     */
    private $idUrlParser;

    /**
     * @var CopyJsonIdentifier
     */
    private $copyJsonIdentifier;

    /**
     * @var CopyJsonName
     */
    private $copyJsonName;

    /**
     * @var CopyJsonTerms
     */
    private $copyJsonTerms;

    /**
     * @var CopyJsonLabels
     */
    private $copyJsonLabels;

    /**
     * @var CopyJsonAddress
     */
    private $copyJsonAddress;

    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    /**
     * @param CopyJsonLoggerInterface $logger
     * @param IdUrlParserInterface $idUrlParser
     * @param FallbackType $fallbackType
     */
    public function __construct(
        CopyJsonLoggerInterface $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        $this->logger = $logger;
        $this->idUrlParser = $idUrlParser;

        $this->copyJsonIdentifier = new CopyJsonIdentifier(
            $logger,
            $idUrlParser,
            $fallbackType
        );

        $this->copyJsonName = new CopyJsonName($logger);

        $this->copyJsonTerms = new CopyJsonTerms();

        $this->copyJsonLabels = new CopyJsonLabels();

        $this->copyJsonAddress = new CopyJsonAddress($logger, true);
    }

    /**
     * @inheritdoc
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (!isset($from->location)) {
            $this->logger->logMissingExpectedField('location');
            return;
        }

        if (!isset($to->location)) {
            $to->location = new \stdClass();
        }

        $this->copyJsonIdentifier->copy($from->location, $to->location);

        if (isset($from->location->duplicatedBy)) {
            $idsOfDuplicates = array_map(
                function (string $iriOfDuplicate) {
                    return $this->idUrlParser->getIdFromUrl($iriOfDuplicate);
                },
                $from->location->duplicatedBy
            );

            $to->location->id = array_merge([$to->location->id], $idsOfDuplicates);
        }

        $this->copyJsonName->copy($from->location, $to->location);

        $this->copyJsonTerms->copy($from->location, $to->location);

        $this->copyJsonLabels->copy($from->location, $to->location);

        $this->copyJsonAddress->copy($from->location, $to);
    }
}

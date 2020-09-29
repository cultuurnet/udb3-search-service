<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\TermsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class RelatedLocationTransformer implements CopyJsonInterface
{
    /**
     * @var IdUrlParserInterface
     */
    private $idUrlParser;

    /**
     * @var IdentifierTransformer
     */
    private $copyJsonIdentifier;

    /**
     * @var NameTransformer
     */
    private $copyJsonName;

    /**
     * @var TermsTransformer
     */
    private $copyJsonTerms;

    /**
     * @var LabelsTransformer
     */
    private $copyJsonLabels;

    /**
     * @var AddressTransformer
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

        $this->copyJsonIdentifier = new IdentifierTransformer(
            $logger,
            $idUrlParser,
            $fallbackType,
            true
        );

        $this->copyJsonName = new NameTransformer($logger);

        $this->copyJsonTerms = new TermsTransformer();

        $this->copyJsonLabels = new LabelsTransformer();

        $this->copyJsonAddress = new AddressTransformer($logger, true);
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

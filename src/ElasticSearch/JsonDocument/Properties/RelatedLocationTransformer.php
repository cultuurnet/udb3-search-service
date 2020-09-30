<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class RelatedLocationTransformer implements CopyJsonInterface
{
    /**
     * @var IdUrlParserInterface
     */
    private $idUrlParser;

    /**
     * @var IdentifierTransformer
     */
    private $identifierTransformer;

    /**
     * @var NameTransformer
     */
    private $nameTransformer;

    /**
     * @var TermsTransformer
     */
    private $termsTransformer;

    /**
     * @var LabelsTransformer
     */
    private $labelsTransformer;

    /**
     * @var AddressTransformer
     */
    private $addressTransformer;

    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @param JsonTransformerLogger $logger
     * @param IdUrlParserInterface $idUrlParser
     * @param FallbackType $fallbackType
     */
    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        $this->logger = $logger;
        $this->idUrlParser = $idUrlParser;

        $this->identifierTransformer = new IdentifierTransformer(
            $logger,
            $idUrlParser,
            $fallbackType,
            true
        );

        $this->nameTransformer = new NameTransformer($logger);

        $this->termsTransformer = new TermsTransformer();

        $this->labelsTransformer = new LabelsTransformer();

        $this->addressTransformer = new AddressTransformer($logger, true);
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

        $this->identifierTransformer->copy($from->location, $to->location);

        if (isset($from->location->duplicatedBy)) {
            $idsOfDuplicates = array_map(
                function (string $iriOfDuplicate) {
                    return $this->idUrlParser->getIdFromUrl($iriOfDuplicate);
                },
                $from->location->duplicatedBy
            );

            $to->location->id = array_merge([$to->location->id], $idsOfDuplicates);
        }

        $this->nameTransformer->copy($from->location, $to->location);

        $this->termsTransformer->copy($from->location, $to->location);

        $this->labelsTransformer->copy($from->location, $to->location);

        $this->addressTransformer->copy($from->location, $to);
    }
}

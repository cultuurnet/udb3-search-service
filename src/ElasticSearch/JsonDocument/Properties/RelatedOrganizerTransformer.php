<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class RelatedOrganizerTransformer implements CopyJsonInterface
{
    /**
     * @var IdentifierTransformer
     */
    private $copyJsonIdentifier;

    /**
     * @var NameTransformer
     */
    private $copyJsonName;

    /**
     * @var LabelsTransformer
     */
    private $copyJsonLabels;

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
        $this->copyJsonIdentifier = new IdentifierTransformer(
            $logger,
            $idUrlParser,
            $fallbackType,
            false
        );

        $this->copyJsonName = new NameTransformer($logger);

        $this->copyJsonLabels = new LabelsTransformer();
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (!isset($from->organizer)) {
            return;
        }

        if (!isset($to->organizer)) {
            $to->organizer = new \stdClass();
        }

        $this->copyJsonIdentifier->copy($from->organizer, $to->organizer);

        $this->copyJsonName->copy($from->organizer, $to->organizer);

        $this->copyJsonLabels->copy($from->organizer, $to->organizer);
    }
}

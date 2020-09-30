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
    private $identifierTransformer;

    /**
     * @var NameTransformer
     */
    private $nameTransformer;

    /**
     * @var LabelsTransformer
     */
    private $labelsTransformer;

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
        $this->identifierTransformer = new IdentifierTransformer(
            $logger,
            $idUrlParser,
            $fallbackType,
            false
        );

        $this->nameTransformer = new NameTransformer($logger);

        $this->labelsTransformer = new LabelsTransformer();
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

        $this->identifierTransformer->copy($from->organizer, $to->organizer);

        $this->nameTransformer->copy($from->organizer, $to->organizer);

        $this->labelsTransformer->copy($from->organizer, $to->organizer);
    }
}

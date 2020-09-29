<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class CreatorTransformer implements CopyJsonInterface
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * CopyJsonCreator constructor.
     *
     * @param JsonTransformerLogger $logger
     */
    public function __construct(JsonTransformerLogger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @inheritdoc
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (!isset($from->creator)) {
            $this->logger->logMissingExpectedField('creator');
            return;
        }

        $to->creator = $from->creator;
    }
}

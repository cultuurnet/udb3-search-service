<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class NameTransformer implements CopyJsonInterface
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * CopyJsonName constructor.
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
        $mainLanguage = isset($from->mainLanguage) ? $from->mainLanguage : 'nl';

        if (!isset($from->name)) {
            $this->logger->logMissingExpectedField('name');
            return;
        }

        if (!isset($from->name->nl)) {
            $this->logger->logMissingExpectedField('name.nl');
        }

        $to->name = $from->name;
    }
}

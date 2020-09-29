<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class NameTransformer implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    /**
     * CopyJsonName constructor.
     * @param CopyJsonLoggerInterface $logger
     */
    public function __construct(CopyJsonLoggerInterface $logger)
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

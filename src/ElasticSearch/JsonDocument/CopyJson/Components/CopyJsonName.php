<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class CopyJsonName implements CopyJsonInterface
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

        // @replay_i18n
        // @see https://jira.uitdatabank.be/browse/III-2201
        if (isset($from->name) && is_string($from->name)) {
            $from = clone $from;
            $from->name = (object) [$mainLanguage => $from->name];
        }

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

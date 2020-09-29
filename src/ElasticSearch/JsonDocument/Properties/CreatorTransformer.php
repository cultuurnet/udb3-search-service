<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class CreatorTransformer implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    /**
     * CopyJsonCreator constructor.
     *
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
        if (!isset($from->creator)) {
            $this->logger->logMissingExpectedField('creator');
            return;
        }

        $to->creator = $from->creator;
    }
}

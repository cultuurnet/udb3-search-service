<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging;

use Psr\Log\LoggerInterface;

class CopyJsonPsrLogger implements CopyJsonLoggerInterface
{
    /** @var  LoggerInterface */
    private $psrLogger;

    /**
     * @param LoggerInterface $psrLogger
     */
    public function __construct(LoggerInterface $psrLogger)
    {
        $this->psrLogger = $psrLogger;
    }

    /**
     * @inheritdoc
     */
    public function logMissingExpectedField($fieldName)
    {
        $this->psrLogger->warning("Missing expected field '{$fieldName}'.");
    }
}

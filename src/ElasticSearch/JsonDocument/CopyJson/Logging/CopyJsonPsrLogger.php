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

    public function logMissingExpectedField(string $fieldName): void
    {
        $this->psrLogger->warning("Missing expected field '{$fieldName}'.");
    }

    public function logError(string $message): void
    {
        $this->psrLogger->error($message);
    }

    public function logWarning(string $message): void
    {
        $this->psrLogger->warning($message);
    }
}

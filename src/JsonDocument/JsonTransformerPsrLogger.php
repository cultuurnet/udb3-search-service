<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use Psr\Log\LoggerInterface;

final class JsonTransformerPsrLogger implements JsonTransformerLogger
{
    private LoggerInterface $psrLogger;


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

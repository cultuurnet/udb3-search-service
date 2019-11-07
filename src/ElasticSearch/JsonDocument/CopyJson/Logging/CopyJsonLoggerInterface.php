<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging;

interface CopyJsonLoggerInterface
{
    public function logMissingExpectedField(string $fieldName): void;

    public function logError(string $message): void;

    public function logWarning(string $message): void;
}

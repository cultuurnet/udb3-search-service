<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

interface JsonTransformerLogger
{
    public function logMissingExpectedField(string $fieldName): void;

    public function logError(string $message): void;

    public function logWarning(string $message): void;
}

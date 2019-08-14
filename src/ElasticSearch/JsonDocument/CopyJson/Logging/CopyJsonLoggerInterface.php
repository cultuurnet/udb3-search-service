<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging;

interface CopyJsonLoggerInterface
{
    /**
     * @param string $fieldName
     */
    public function logMissingExpectedField($fieldName);
}

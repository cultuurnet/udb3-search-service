<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class NameTransformer implements JsonTransformer
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @param JsonTransformerLogger $logger
     */
    public function __construct(JsonTransformerLogger $logger)
    {
        $this->logger = $logger;
    }

    public function transform(array $from, array $draft = []): array
    {
        $mainLanguage = $from['mainLanguage'] ?? 'nl';

        if (!isset($from['name'])) {
            $this->logger->logMissingExpectedField('name');
            return $draft;
        }

        if (!isset($from['name'][$mainLanguage])) {
            $this->logger->logMissingExpectedField('name.' . $mainLanguage);
        }

        $draft['name'] = $from['name'];
        return $draft;
    }
}

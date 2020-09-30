<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class CreatorTransformer implements JsonTransformer
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
        if (!isset($from['creator'])) {
            $this->logger->logMissingExpectedField('creator');
            return $draft;
        }

        $draft['creator'] = $from['creator'];
        return $draft;
    }
}

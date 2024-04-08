<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class WorkflowStatusTransformer implements JsonTransformer
{
    private JsonTransformerLogger $logger;

    private ?string $default;

    public function __construct(JsonTransformerLogger $logger, ?string $default = null)
    {
        $this->logger = $logger;
        $this->default = $default;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (isset($from['workflowStatus'])) {
            $draft['workflowStatus'] = $from['workflowStatus'];
            return $draft;
        }

        if (!is_null($this->default)) {
            $draft['workflowStatus'] = $this->default;
            return $draft;
        }

        $this->logger->logMissingExpectedField('workflowStatus');
        return $draft;
    }
}

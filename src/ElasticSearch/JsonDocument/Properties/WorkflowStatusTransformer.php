<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use stdClass;

class WorkflowStatusTransformer implements CopyJsonInterface
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @var string|null
     */
    private $default;

    public function __construct(JsonTransformerLogger $logger, ?string $default = null)
    {
        $this->logger = $logger;
        $this->default = $default;
    }

    /**
     * @inheritdoc
     */
    public function copy(stdClass $from, stdClass $to)
    {
        if (isset($from->workflowStatus)) {
            $to->workflowStatus = $from->workflowStatus;
            return;
        }

        if (!is_null($this->default)) {
            $to->workflowStatus = $this->default;
            return;
        }

        $this->logger->logMissingExpectedField('workflowStatus');
    }
}

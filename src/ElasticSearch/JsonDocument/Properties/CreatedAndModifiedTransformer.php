<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use DateTimeImmutable;
use stdClass;

class CreatedAndModifiedTransformer implements CopyJsonInterface
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @param JsonTransformerLogger $logger
     */
    public function __construct(
        JsonTransformerLogger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function copy(stdClass $from, stdClass $to)
    {
        if (!isset($from->created)) {
            $this->logger->logMissingExpectedField('created');
            return;
        }

        $created = DateTimeImmutable::createFromFormat(\DateTime::ATOM, $from->created);

        if (!$created) {
            $this->logger->logError('Could not parse created as an ISO-8601 datetime.');
            return;
        }

        $to->created = $created->format(\DateTime::ATOM);

        if (!isset($from->modified)) {
            return;
        }

        $modified = DateTimeImmutable::createFromFormat(\DateTime::ATOM, $from->modified);

        if (!$modified) {
            $this->logger->logError('Could not parse modified as an ISO-8601 datetime.');
            return;
        }

        $to->modified = $modified->format(\DateTime::ATOM);
    }
}

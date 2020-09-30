<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use DateTimeImmutable;

final class CreatedAndModifiedTransformer implements JsonTransformer
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

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['created'])) {
            $this->logger->logMissingExpectedField('created');
            return $draft;
        }

        $created = DateTimeImmutable::createFromFormat(\DateTime::ATOM, $from['created']);

        if (!$created) {
            $this->logger->logError('Could not parse created as an ISO-8601 datetime.');
            return $draft;
        }

        $draft['created'] = $created->format(\DateTime::ATOM);

        if (!isset($from['modified'])) {
            return $draft;
        }

        $modified = DateTimeImmutable::createFromFormat(\DateTime::ATOM, $from['modified']);

        if (!$modified) {
            $this->logger->logError('Could not parse modified as an ISO-8601 datetime.');
            return $draft;
        }

        $draft['modified'] = $modified->format(\DateTime::ATOM);

        return $draft;
    }
}

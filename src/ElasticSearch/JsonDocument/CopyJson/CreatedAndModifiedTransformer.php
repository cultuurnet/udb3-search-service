<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\TermsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;
use DateTimeImmutable;
use stdClass;

class CreatedAndModifiedTransformer implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    /**
     * @param CopyJsonLoggerInterface $logger
     */
    public function __construct(
        CopyJsonLoggerInterface $logger
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

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class NameTransformer implements JsonTransformer
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

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

        // @replay_i18n
        // @see https://jira.uitdatabank.be/browse/III-3584
        // @see https://jira.uitdatabank.be/browse/III-2201
        if (is_string($from['name'])) {
            $from['name'] = [
                $mainLanguage => $from['name'],
            ];
        }

        if (!isset($from['name'][$mainLanguage])) {
            $this->logger->logMissingExpectedField('name.' . $mainLanguage);
        }

        $draft['name'] = $from['name'];
        return $draft;
    }
}

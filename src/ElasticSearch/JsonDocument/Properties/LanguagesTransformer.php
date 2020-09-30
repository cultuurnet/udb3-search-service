<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class LanguagesTransformer implements JsonTransformer
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
        if (isset($from['languages'])) {
            $languages = $from['languages'];
        } else {
            $this->logger->logMissingExpectedField('languages');
        }

        if (isset($from['completedLanguages'])) {
            $completedLanguages = $from['completedLanguages'];
        } else {
            $this->logger->logMissingExpectedField('completedLanguages');
        }

        if (!empty($languages)) {
            $draft['languages'] = $languages;
        }

        if (!empty($completedLanguages)) {
            $draft['completedLanguages'] = $completedLanguages;
        }

        return $draft;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class LanguagesTransformer implements JsonTransformer
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @var bool
     */
    private $mainLanguageRequired;

    public function __construct(JsonTransformerLogger $logger, bool $mainLanguageRequired)
    {
        $this->logger = $logger;
        $this->mainLanguageRequired = $mainLanguageRequired;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (isset($from['mainLanguage'])) {
            $draft['mainLanguage'] = $from['mainLanguage'];
        } elseif ($this->mainLanguageRequired) {
            $this->logger->logMissingExpectedField('mainLanguage');
        }

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

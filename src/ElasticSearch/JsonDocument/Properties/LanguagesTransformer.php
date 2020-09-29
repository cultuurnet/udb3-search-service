<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class LanguagesTransformer implements CopyJsonInterface
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    public function __construct(JsonTransformerLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (isset($from->languages)) {
            $languages = $from->languages;
        } else {
            $this->logger->logMissingExpectedField('languages');
        }

        if (isset($from->completedLanguages)) {
            $completedLanguages = $from->completedLanguages;
        } else {
            $this->logger->logMissingExpectedField('completedLanguages');
        }

        if (!empty($languages)) {
            $to->languages = $languages;
        }

        if (!empty($completedLanguages)) {
            $to->completedLanguages = $completedLanguages;
        }
    }
}

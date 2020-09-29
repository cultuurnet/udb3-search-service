<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\JsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use Rhumsaa\Uuid\Uuid;

class LanguagesTransformer implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    public function __construct(CopyJsonLoggerInterface $logger)
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

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageAnalyzerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use Rhumsaa\Uuid\Uuid;

class CopyJsonLanguages implements CopyJsonInterface
{
    /**
     * @var JsonDocumentLanguageAnalyzerInterface
     */
    private $languageAnalyzer;

    /**
     * @param JsonDocumentLanguageAnalyzerInterface $languageAnalyzer
     */
    public function __construct(JsonDocumentLanguageAnalyzerInterface $languageAnalyzer)
    {
        $this->languageAnalyzer = $languageAnalyzer;
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $languageToString = function (Language $language) {
            return $language->getCode();
        };

        if (isset($from->languages)) {
            $languages = $from->languages;
        } else {
            // @todo Change this else condition to log missing field when full
            // replay is done.
            // @replay_i18n
            // @see https://jira.uitdatabank.be/browse/III-2201
            // Use NIL uuid as it doesn't really matter here. The JsonDocument is
            // just a wrapper to pass the $to JSON to the language analyzer.
            $jsonDocument = new JsonDocument(Uuid::NIL, json_encode($to));
            $languages = $this->languageAnalyzer->determineAvailableLanguages($jsonDocument);
            $languages = array_map($languageToString, $languages);
        }

        if (isset($from->completedLanguages)) {
            $completedLanguages = $from->completedLanguages;
        } else {
            // @todo Change this else condition to log missing field when full
            // replay is done.
            // @replay_i18n
            // @see https://jira.uitdatabank.be/browse/III-2201
            // Use NIL uuid as it doesn't really matter here. The JsonDocument is
            // just a wrapper to pass the $to JSON to the language analyzer.
            $jsonDocument = new JsonDocument(Uuid::NIL, json_encode($to));
            $completedLanguages = $this->languageAnalyzer->determineCompletedLanguages($jsonDocument);
            $completedLanguages = array_map($languageToString, $completedLanguages);
        }

        if (!empty($languages)) {
            $to->languages = $languages;
        }

        if (!empty($completedLanguages)) {
            $to->completedLanguages = $completedLanguages;
        }
    }
}

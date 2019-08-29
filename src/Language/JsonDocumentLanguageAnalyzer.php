<?php

namespace CultuurNet\UDB3\Search\Language;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

interface JsonDocumentLanguageAnalyzer
{
    /**
     * @param JsonDocument $jsonDocument
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument);

    /**
     * @param JsonDocument $jsonDocument
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument);
}

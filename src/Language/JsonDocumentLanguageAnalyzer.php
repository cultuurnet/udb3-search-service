<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Language;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

interface JsonDocumentLanguageAnalyzer
{
    /**
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument);

    /**
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument);
}

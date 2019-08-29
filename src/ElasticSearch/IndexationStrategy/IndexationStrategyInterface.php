<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use ValueObjects\StringLiteral\StringLiteral;

interface IndexationStrategyInterface
{
    /**
     * @param StringLiteral $indexName
     * @param StringLiteral $documentType
     * @param JsonDocument $jsonDocument
     */
    public function indexDocument(
        StringLiteral $indexName,
        StringLiteral $documentType,
        JsonDocument $jsonDocument
    );
}

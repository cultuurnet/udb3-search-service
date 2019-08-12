<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use ValueObjects\StringLiteral\StringLiteral;

class MutableIndexationStrategy implements IndexationStrategyInterface
{
    /**
     * @var IndexationStrategyInterface
     */
    private $indexationStrategy;

    /**
     * @param IndexationStrategyInterface $indexationStrategy
     */
    public function __construct(IndexationStrategyInterface $indexationStrategy)
    {
        $this->indexationStrategy = $indexationStrategy;
    }

    /**
     * @param IndexationStrategyInterface $newIndexationStrategy
     */
    public function setIndexationStrategy(IndexationStrategyInterface $newIndexationStrategy)
    {
        if ($this->indexationStrategy instanceof BulkIndexationStrategy) {
            $this->indexationStrategy->flush();
        }

        $this->indexationStrategy = $newIndexationStrategy;
    }

    /**
     * @param StringLiteral $indexName
     * @param StringLiteral $documentType
     * @param JsonDocument $jsonDocument
     */
    public function indexDocument(
        StringLiteral $indexName,
        StringLiteral $documentType,
        JsonDocument $jsonDocument
    ) {
        $this->indexationStrategy->indexDocument($indexName, $documentType, $jsonDocument);
    }
}

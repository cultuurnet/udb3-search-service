<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use ValueObjects\StringLiteral\StringLiteral;

final class MutableIndexationStrategy implements IndexationStrategyInterface
{
    /**
     * @var IndexationStrategyInterface
     */
    private $indexationStrategy;


    public function __construct(IndexationStrategyInterface $indexationStrategy)
    {
        $this->indexationStrategy = $indexationStrategy;
    }


    public function setIndexationStrategy(IndexationStrategyInterface $newIndexationStrategy)
    {
        if ($this->indexationStrategy instanceof BulkIndexationStrategyInterface) {
            $this->indexationStrategy->flush();
        }

        $this->indexationStrategy = $newIndexationStrategy;
    }


    public function indexDocument(
        StringLiteral $indexName,
        StringLiteral $documentType,
        JsonDocument $jsonDocument
    ) {
        $this->indexationStrategy->indexDocument($indexName, $documentType, $jsonDocument);
    }
}

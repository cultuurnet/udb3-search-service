<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

final class MutableIndexationStrategy implements IndexationStrategy
{
    /**
     * @var IndexationStrategy
     */
    private $indexationStrategy;


    public function __construct(IndexationStrategy $indexationStrategy)
    {
        $this->indexationStrategy = $indexationStrategy;
    }


    public function setIndexationStrategy(IndexationStrategy $newIndexationStrategy)
    {
        $this->indexationStrategy->finish();
        $this->indexationStrategy = $newIndexationStrategy;
    }


    public function indexDocument(
        string $indexName,
        string $documentType,
        JsonDocument $jsonDocument
    ) {
        $this->indexationStrategy->indexDocument($indexName, $documentType, $jsonDocument);
    }

    public function finish(): void
    {
    }
}

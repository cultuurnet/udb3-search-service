<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

final class MutableIndexationStrategy implements IndexationStrategy
{
    private IndexationStrategy $indexationStrategy;


    public function __construct(IndexationStrategy $indexationStrategy)
    {
        $this->indexationStrategy = $indexationStrategy;
    }


    public function setIndexationStrategy(IndexationStrategy $newIndexationStrategy): void
    {
        $this->indexationStrategy->finish();
        $this->indexationStrategy = $newIndexationStrategy;
    }


    public function indexDocument(
        string $indexName,
        JsonDocument $jsonDocument
    ): void {
        $this->indexationStrategy->indexDocument($indexName, $jsonDocument);
    }

    public function finish(): void
    {
    }
}

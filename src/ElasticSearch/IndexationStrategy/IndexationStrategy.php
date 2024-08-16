<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

interface IndexationStrategy
{
    public function indexDocument(
        string $indexName,
        string $documentType,
        JsonDocument $jsonDocument
    ): void;

    public function finish(): void;
}

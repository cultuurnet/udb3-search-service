<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

interface JsonDocumentFetcherInterface
{
    public function withIncludeMetadata(): JsonDocumentFetcherInterface;

    public function fetch(string $documentId, string $documentIri): ?JsonDocument;
}

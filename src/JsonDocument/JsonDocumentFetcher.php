<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

interface JsonDocumentFetcher
{
    /** @return static */
    public function withIncludeMetadata();

    public function fetch(string $documentId, string $documentIri): ?JsonDocument;
}

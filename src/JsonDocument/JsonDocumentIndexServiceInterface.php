<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

interface JsonDocumentIndexServiceInterface
{
    public function index(string $documentId, string $documentIri): void;

    public function remove(string $documentId): void;
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ReadModel;

interface DocumentRepository
{
    public function get(string $id): ?JsonDocument;

    public function save(JsonDocument $readModel): void;

    public function remove(string $id): void;

    public function getDocumentType(): string;
}

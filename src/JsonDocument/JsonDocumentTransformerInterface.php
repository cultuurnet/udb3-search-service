<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

interface JsonDocumentTransformerInterface
{
    public function transform(JsonDocument $jsonDocument): JsonDocument;
}

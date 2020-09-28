<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

class PassThroughJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument;
    }
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;

class JsonLdEmbeddingJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $body = $jsonDocument->getBody();

        return new JsonDocument(
            $jsonDocument->getId(),
            $body->originalEncodedJsonLd
        );
    }
}

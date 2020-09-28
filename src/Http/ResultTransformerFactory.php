<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;

class ResultTransformerFactory
{
    public static function create(bool $embedded): JsonDocumentTransformerInterface
    {
        if ($embedded) {
            return new JsonLdEmbeddingJsonDocumentTransformer();
        }

        return new MinimalRequiredInfoJsonDocumentTransformer();
    }
}

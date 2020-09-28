<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

class ResultTransformerFactory
{
    public static function create(bool $embedded): JsonTransformer
    {
        if ($embedded) {
            return new JsonLdEmbeddingJsonTransformer();
        }

        return new MinimalRequiredInfoJsonTransformer();
    }
}

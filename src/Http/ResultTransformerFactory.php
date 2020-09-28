<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\RegionEmbeddingJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

class ResultTransformerFactory
{
    public static function create(bool $embedded): JsonTransformer
    {
        if ($embedded) {
            return new CompositeJsonTransformer(
                new JsonLdEmbeddingJsonTransformer(),
                new RegionEmbeddingJsonTransformer()
            );
        }

        return new MinimalRequiredInfoJsonTransformer();
    }
}

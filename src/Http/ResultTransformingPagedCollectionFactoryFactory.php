<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;

/**
 * @TODO: Rename
 */
class ResultTransformingPagedCollectionFactoryFactory
{
    public function create(bool $embedded): ResultTransformingPagedCollectionFactory
    {
        return new ResultTransformingPagedCollectionFactory(
            $this->createTransformer($embedded)
        );
    }
    
    private function createTransformer(bool $embedded): JsonDocumentTransformerInterface
    {
        if ($embedded) {
            return new JsonLdEmbeddingJsonDocumentTransformer();
        }

        return new MinimalRequiredInfoJsonDocumentTransformer();
    }
}

<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonDocumentTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonDocumentTransformer;
use CultuurNet\UDB3\Search\Http\Value\Embedded;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;

/**
 * Class ResultTransformingPagedCollectionFactoryFactory
 * @package CultuurNet\UDB3\Search\Http
 * @TODO: Rename
 */
class ResultTransformingPagedCollectionFactoryFactory
{
    
    public function create(Embedded $embedded): ResultTransformingPagedCollectionFactory
    {
        return new ResultTransformingPagedCollectionFactory(
            $this->createTransformer($embedded)
        );
    }
    
    private function createTransformer(Embedded $embedded): JsonDocumentTransformerInterface
    {
        if ($embedded->isTrue()) {
            return new JsonLdEmbeddingJsonDocumentTransformer();
        }
        
        return new MinimalRequiredInfoJsonDocumentTransformer();
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

final class CompositeAggregationTransformer implements AggregationTransformerInterface
{
    /**
     * @var AggregationTransformerInterface[]
     */
    private $transformers = [];


    public function register(AggregationTransformerInterface $aggregationTransformer)
    {
        $this->transformers[] = $aggregationTransformer;
    }

    /**
     * @return bool
     */
    public function supports(Aggregation $aggregation)
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($aggregation)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return FacetTreeInterface
     * @throws \LogicException
     */
    public function toFacetTree(Aggregation $aggregation)
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($aggregation)) {
                return $transformer->toFacetTree($aggregation);
            }
        }

        $aggregationName = $aggregation->getName()->toString();
        throw new \LogicException("Aggregation \"$aggregationName\" not supported for transformation.");
    }
}

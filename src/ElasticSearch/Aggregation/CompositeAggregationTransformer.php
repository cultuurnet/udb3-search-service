<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

final class CompositeAggregationTransformer implements AggregationTransformerInterface
{
    /**
     * @var AggregationTransformerInterface[]
     */
    private array $transformers = [];


    public function register(AggregationTransformerInterface $aggregationTransformer): void
    {
        $this->transformers[] = $aggregationTransformer;
    }


    public function supports(Aggregation $aggregation): bool
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($aggregation)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws LogicException
     */
    public function toFacetTree(Aggregation $aggregation): FacetTreeInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($aggregation)) {
                return $transformer->toFacetTree($aggregation);
            }
        }

        $aggregationName = $aggregation->getName()->toString();
        throw new LogicException("Aggregation \"$aggregationName\" not supported for transformation.");
    }
}

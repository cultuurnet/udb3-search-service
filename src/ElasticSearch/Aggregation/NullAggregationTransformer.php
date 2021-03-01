<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

class NullAggregationTransformer implements AggregationTransformerInterface
{
    /**
     * @inheritdoc
     */
    public function supports(Aggregation $aggregation)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function toFacetTree(Aggregation $aggregation)
    {
        throw new \LogicException('NullAggregationTransformer does not support any aggregations for transformation.');
    }
}

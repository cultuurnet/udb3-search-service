<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
final class NullAggregationTransformer implements AggregationTransformerInterface
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
    public function toFacetTree(Aggregation $aggregation): void
    {
        throw new LogicException('NullAggregationTransformer does not support any aggregations for transformation.');
    }
}

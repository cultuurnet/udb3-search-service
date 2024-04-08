<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;
use LogicException;

final class NullAggregationTransformer implements AggregationTransformerInterface
{
    /**
     * @inheritdoc
     */
    public function supports(Aggregation $aggregation): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     * @return never
     */
    public function toFacetTree(Aggregation $aggregation): FacetTreeInterface
    {
        throw new LogicException('NullAggregationTransformer does not support any aggregations for transformation.');
    }
}

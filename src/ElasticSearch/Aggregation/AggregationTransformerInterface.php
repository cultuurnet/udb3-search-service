<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

interface AggregationTransformerInterface
{
    /**
     * @param Aggregation $aggregation
     * @return bool
     */
    public function supports(Aggregation $aggregation);

    /**
     * @param Aggregation $aggregation
     * @return FacetTreeInterface
     * @throws \LogicException
     *   If the transformer does not support this particular aggregation.
     */
    public function toFacetTree(Aggregation $aggregation);
}

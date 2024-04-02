<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

interface AggregationTransformerInterface
{
    /**
     * @return bool
     */
    public function supports(Aggregation $aggregation);

    /**
     * @return FacetTreeInterface
     * @throws LogicException
     *   If the transformer does not support this particular aggregation.
     */
    public function toFacetTree(Aggregation $aggregation);
}

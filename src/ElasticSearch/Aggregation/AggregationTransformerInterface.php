<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

interface AggregationTransformerInterface
{
    public function supports(Aggregation $aggregation): bool;

    /**
     * @throws LogicException
     *   If the transformer does not support this particular aggregation.
     */
    public function toFacetTree(Aggregation $aggregation): FacetTreeInterface;
}

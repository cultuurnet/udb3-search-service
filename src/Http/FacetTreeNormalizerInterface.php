<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

interface FacetTreeNormalizerInterface
{
    /**
     *   Array with exclusively scalar values.
     */
    public function normalize(FacetTreeInterface $facetTree): array;
}

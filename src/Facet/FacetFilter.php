<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

use CultuurNet\UDB3\Search\Offer\FacetName;

final class FacetFilter extends AbstractFacetTree
{
    private FacetName $facetName;

    public function __construct(FacetName $facetName, array $children = [])
    {
        parent::__construct($facetName->value, $children);
        $this->facetName = $facetName;
    }

    public function getFacetName(): FacetName
    {
        return $this->facetName;
    }
}

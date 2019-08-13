<?php

namespace CultuurNet\UDB3\Search\Facet;

interface FacetTreeInterface
{
    /**
     * @return string
     */
    public function getKey();

    /**
     * @return FacetNodeInterface[]
     */
    public function getChildren();
}

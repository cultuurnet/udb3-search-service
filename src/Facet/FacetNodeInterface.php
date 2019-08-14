<?php

namespace CultuurNet\UDB3\Search\Facet;

interface FacetNodeInterface extends FacetTreeInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return int
     */
    public function getCount();
}

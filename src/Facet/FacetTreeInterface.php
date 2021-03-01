<?php

declare(strict_types=1);

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

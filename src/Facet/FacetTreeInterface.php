<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

interface FacetTreeInterface
{
    public function getKey(): string;

    /**
     * @return FacetNodeInterface[]
     */
    public function getChildren(): array;
}

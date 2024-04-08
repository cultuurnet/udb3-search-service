<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

interface FacetTreeInterface
{
    public function getKey(): string;

    /**
     * @return FacetNode[]
     */
    public function getChildren(): array;
}

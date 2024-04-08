<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

interface FacetNodeInterface extends FacetTreeInterface
{
    public function getLabel(): string;

    public function getCount(): int;
}

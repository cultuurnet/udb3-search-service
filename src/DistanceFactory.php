<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

interface DistanceFactory
{
    /**
     * @param string $distance
     * @return AbstractDistance
     */
    public function fromString($distance);
}

<?php

namespace CultuurNet\UDB3\Search;

interface DistanceFactoryInterface
{
    /**
     * @param string $distance
     * @return AbstractDistance
     */
    public function fromString($distance);
}

<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\DistanceFactoryInterface;

class ElasticSearchDistanceFactory implements DistanceFactoryInterface
{
    /**
     * @param string $distance
     * @return ElasticSearchDistance
     */
    public function fromString($distance)
    {
        return new ElasticSearchDistance($distance);
    }
}

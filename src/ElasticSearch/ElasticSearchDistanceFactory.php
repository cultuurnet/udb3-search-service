<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\DistanceFactory;

final class ElasticSearchDistanceFactory implements DistanceFactory
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

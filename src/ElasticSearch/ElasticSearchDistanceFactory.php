<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\DistanceFactory;

final class ElasticSearchDistanceFactory implements DistanceFactory
{
    /**
     * @param string $distance
     */
    public function fromString($distance): ElasticSearchDistance
    {
        return new ElasticSearchDistance($distance);
    }
}

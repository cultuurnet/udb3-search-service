<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\DistanceFactory;

final class ElasticSearchDistanceFactory implements DistanceFactory
{
    public function fromString(string $distance): ElasticSearchDistance
    {
        return new ElasticSearchDistance($distance);
    }
}

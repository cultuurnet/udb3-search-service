<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\DistanceFactory;

final class MockDistanceFactory implements DistanceFactory
{
    /**
     * @param string $distance
     */
    public function fromString($distance): MockDistance
    {
        return new MockDistance($distance);
    }
}

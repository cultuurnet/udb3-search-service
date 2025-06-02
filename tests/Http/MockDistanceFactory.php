<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\DistanceFactory;

final class MockDistanceFactory implements DistanceFactory
{
    public function fromString(string $distance): MockDistance
    {
        return new MockDistance($distance);
    }
}

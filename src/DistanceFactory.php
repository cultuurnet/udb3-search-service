<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

interface DistanceFactory
{
    public function fromString(string $distance): AbstractDistance;
}

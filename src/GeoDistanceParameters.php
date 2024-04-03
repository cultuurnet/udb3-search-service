<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;

final class GeoDistanceParameters
{
    private Coordinates $coordinates;

    private AbstractDistance $maximumDistance;


    public function __construct(
        Coordinates $coordinates,
        AbstractDistance $maximumDistance
    ) {
        $this->coordinates = $coordinates;
        $this->maximumDistance = $maximumDistance;
    }


    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }


    public function getMaximumDistance(): AbstractDistance
    {
        return $this->maximumDistance;
    }
}

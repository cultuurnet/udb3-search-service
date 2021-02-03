<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;

class GeoDistanceParameters
{
    /**
     * @var Coordinates
     */
    private $coordinates;

    /**
     * @var AbstractDistance
     */
    private $maximumDistance;

    /**
     * @param Coordinates $coordinates
     * @param AbstractDistance $maximumDistance
     */
    public function __construct(
        Coordinates $coordinates,
        AbstractDistance $maximumDistance
    ) {
        $this->coordinates = $coordinates;
        $this->maximumDistance = $maximumDistance;
    }

    /**
     * @return Coordinates
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @return AbstractDistance
     */
    public function getMaximumDistance()
    {
        return $this->maximumDistance;
    }
}

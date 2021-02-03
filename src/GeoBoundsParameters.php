<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;

class GeoBoundsParameters
{
    /**
     * @var Coordinates
     */
    private $northWestCoordinates;

    /**
     * @var Coordinates
     */
    private $northEastCoordinates;

    /**
     * @var Coordinates
     */
    private $southWestCoordinates;

    /**
     * @var Coordinates
     */
    private $southEastCoordinates;

    public function __construct(Coordinates $northEastCoordinates, Coordinates $southWestCoordinates)
    {
        $this->northEastCoordinates = $northEastCoordinates;
        $this->southWestCoordinates = $southWestCoordinates;

        $this->northWestCoordinates = new Coordinates(
            $northEastCoordinates->getLatitude(),
            $southWestCoordinates->getLongitude()
        );

        $this->southEastCoordinates = new Coordinates(
            $southWestCoordinates->getLatitude(),
            $northEastCoordinates->getLongitude()
        );
    }

    /**
     * @return Coordinates
     */
    public function getNorthWestCoordinates()
    {
        return $this->northWestCoordinates;
    }

    /**
     * @return Coordinates
     */
    public function getNorthEastCoordinates()
    {
        return $this->northEastCoordinates;
    }

    /**
     * @return Coordinates
     */
    public function getSouthWestCoordinates()
    {
        return $this->southWestCoordinates;
    }

    /**
     * @return Coordinates
     */
    public function getSouthEastCoordinates()
    {
        return $this->southEastCoordinates;
    }
}

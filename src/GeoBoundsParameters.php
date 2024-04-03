<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;

final class GeoBoundsParameters
{
    private Coordinates $northWestCoordinates;

    private Coordinates $northEastCoordinates;

    private Coordinates $southWestCoordinates;

    private Coordinates $southEastCoordinates;

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
    public function getNorthWestCoordinates(): Coordinates
    {
        return $this->northWestCoordinates;
    }

    /**
     * @return Coordinates
     */
    public function getNorthEastCoordinates(): Coordinates
    {
        return $this->northEastCoordinates;
    }

    /**
     * @return Coordinates
     */
    public function getSouthWestCoordinates(): Coordinates
    {
        return $this->southWestCoordinates;
    }

    /**
     * @return Coordinates
     */
    public function getSouthEastCoordinates(): Coordinates
    {
        return $this->southEastCoordinates;
    }
}

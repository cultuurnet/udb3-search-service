<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use PHPUnit\Framework\TestCase;

final class GeoBoundsParametersTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_north_east_and_south_west_to_north_west_and_south_east()
    {
        $givenNorthEast = new Coordinates(
            new Latitude(34.2355209),
            new Longitude(-118.5534191)
        );

        $givenSouthWest = new Coordinates(
            new Latitude(34.1854649),
            new Longitude(-118.588536)
        );

        $expectedNorthWest = new Coordinates(
            new Latitude(34.2355209),
            new Longitude(-118.588536)
        );

        $expectedSouthEast = new Coordinates(
            new Latitude(34.1854649),
            new Longitude(-118.5534191)
        );

        $geoBounds = new GeoBoundsParameters($givenNorthEast, $givenSouthWest);

        $this->assertEquals($givenNorthEast, $geoBounds->getNorthEastCoordinates());
        $this->assertEquals($givenSouthWest, $geoBounds->getSouthWestCoordinates());

        $this->assertEquals($expectedNorthWest, $geoBounds->getNorthWestCoordinates());
        $this->assertEquals($expectedSouthEast, $geoBounds->getSouthEastCoordinates());
    }
}

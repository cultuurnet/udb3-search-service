<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use PHPUnit\Framework\TestCase;

final class GeoDistanceParametersTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_coordinates_and_a_maximum_distance()
    {
        $coordinates = new Coordinates(
            new Latitude(40.443567),
            new Longitude(-70.559987)
        );

        // http://units.wikia.com/wiki/Beard-second#Beard-second
        $maxDistance = new MockDistance('30 beard-seconds');

        $parameters = new GeoDistanceParameters(
            $coordinates,
            $maxDistance
        );

        $this->assertEquals($coordinates, $parameters->getCoordinates());
        $this->assertEquals($maxDistance, $parameters->getMaximumDistance());
    }
}

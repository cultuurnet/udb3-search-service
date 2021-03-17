<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Geocoding\Coordinate;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class Coordinates
{
    /**
     * @var Latitude
     */
    private $lat;

    /**
     * @var Longitude
     */
    private $long;

    public function __construct(Latitude $lat, Longitude $long)
    {
        $this->lat = $lat;
        $this->long = $long;
    }

    public function getLatitude(): Latitude
    {
        return $this->lat;
    }

    public function getLongitude(): Longitude
    {
        return $this->long;
    }

    public function sameAs(Coordinates $coordinates): bool
    {
        return $coordinates->getLatitude()->sameAs($this->lat) &&
            $coordinates->getLongitude()->sameAs($this->long);
    }

    public static function fromLatLonString(string $latLon): Coordinates
    {
        $split = explode(',', $latLon);

        if (count($split) !== 2) {
            throw new UnsupportedParameterValue('Lat lon string is not in the expected format (lat,lon).');
        }

        $lat = new Latitude((float) $split[0]);
        $lon = new Longitude((float) $split[1]);

        return new Coordinates($lat, $lon);
    }
}

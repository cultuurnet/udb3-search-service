<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use CultuurNet\UDB3\Search\GeoBoundsParameters;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class GeoBoundsParametersFactory
{
    private const BOUNDS_REGEX = '([0-9\.-]+),([0-9\.-]+)\|([0-9\.-]+),([0-9\.-]+)';

    public function fromApiRequest(ApiRequestInterface $apiRequest): ?GeoBoundsParameters
    {
        $bounds = $apiRequest->getQueryParam('bounds', false);

        if (!$bounds) {
            return null;
        }

        $matches = [];
        if (!preg_match('/' . self::BOUNDS_REGEX . '/', $bounds, $matches)) {
            throw new UnsupportedParameterValue(
                'Bounds parameter should be in the "southWestLat,southWestLong|northEastLat,NorthEastLong" format.'
            );
        }

        $southWestLat = (float) $matches[1];
        $southWestLong = (float) $matches[2];
        $northEastLat = (float) $matches[3];
        $northEastLong = (float) $matches[4];

        $southWest = new Coordinates(
            new Latitude($southWestLat),
            new Longitude($southWestLong)
        );

        $northEast = new Coordinates(
            new Latitude($northEastLat),
            new Longitude($northEastLong)
        );

        return new GeoBoundsParameters($northEast, $southWest);
    }
}

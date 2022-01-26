<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use CultuurNet\UDB3\Search\DistanceFactory;
use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\MissingParameter;

final class GeoDistanceParametersFactory
{
    private DistanceFactory $distanceFactory;

    public function __construct(DistanceFactory $distanceFactory)
    {
        $this->distanceFactory = $distanceFactory;
    }

    public function fromApiRequest(ApiRequestInterface $apiRequest): ?GeoDistanceParameters
    {
        $coordinates = $apiRequest->getQueryParam('coordinates', false);
        $distance = $apiRequest->getQueryParam('distance', false);

        if (!$coordinates && !$distance) {
            return null;
        }

        if ($coordinates && !$distance) {
            throw new MissingParameter('Required "distance" parameter missing when searching by coordinates.');
        }

        if ($distance && !$coordinates) {
            throw new MissingParameter('Required "coordinates" parameter missing when searching by distance.');
        }

        $coordinates = Coordinates::fromLatLonString($coordinates);

        return new GeoDistanceParameters(
            $coordinates,
            $this->distanceFactory->fromString($distance)
        );
    }
}

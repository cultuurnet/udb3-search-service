<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\DistanceFactoryInterface;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

class DistanceOfferRequestParser implements OfferRequestParserInterface
{
    /**
     * @var DistanceFactoryInterface
     */
    private $distanceFactory;

    public function __construct(DistanceFactoryInterface $distanceFactory)
    {
        $this->distanceFactory = $distanceFactory;
    }

    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $coordinates = $request->getQueryParam('coordinates', false);
        $distance = $request->getQueryParam('distance', false);

        if ($coordinates && !$distance) {
            throw new \InvalidArgumentException('Required "distance" parameter missing when searching by coordinates.');
        } elseif ($distance && !$coordinates) {
            throw new \InvalidArgumentException('Required "coordinates" parameter missing when searching by distance.');
        } elseif ($coordinates && $distance) {
            $coordinates = Coordinates::fromLatLonString($coordinates);

            $offerQueryBuilder = $offerQueryBuilder->withGeoDistanceFilter(
                new GeoDistanceParameters(
                    $coordinates,
                    $this->distanceFactory->fromString($distance)
                )
            );
        }

        return $offerQueryBuilder;
    }
}

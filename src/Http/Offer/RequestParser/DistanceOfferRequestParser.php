<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\DistanceFactory;
use CultuurNet\UDB3\Search\GeoDistanceParameters;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class DistanceOfferRequestParser implements OfferRequestParserInterface
{
    /**
     * @var DistanceFactory
     */
    private $distanceFactory;

    public function __construct(DistanceFactory $distanceFactory)
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
            throw new MissingParameter('Required "distance" parameter missing when searching by coordinates.');
        } elseif ($distance && !$coordinates) {
            throw new MissingParameter('Required "coordinates" parameter missing when searching by distance.');
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

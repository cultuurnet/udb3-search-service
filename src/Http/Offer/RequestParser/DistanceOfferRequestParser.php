<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\GeoDistanceParametersFactory;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class DistanceOfferRequestParser implements OfferRequestParserInterface
{
    private GeoDistanceParametersFactory $geoDistanceParametersFactory;

    public function __construct(GeoDistanceParametersFactory $geoDistanceParametersFactory)
    {
        $this->geoDistanceParametersFactory = $geoDistanceParametersFactory;
    }

    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $geoDistanceParameters = $this->geoDistanceParametersFactory->fromApiRequest($request);

        if ($geoDistanceParameters === null) {
            return $offerQueryBuilder;
        }

        return $offerQueryBuilder->withGeoDistanceFilter($geoDistanceParameters);
    }
}

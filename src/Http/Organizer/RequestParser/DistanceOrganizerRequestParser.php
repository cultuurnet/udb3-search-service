<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\GeoDistanceParametersFactory;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class DistanceOrganizerRequestParser implements OrganizerRequestParser
{
    private GeoDistanceParametersFactory $geoDistanceParametersFactory;

    public function __construct(GeoDistanceParametersFactory $geoDistanceParametersFactory)
    {
        $this->geoDistanceParametersFactory = $geoDistanceParametersFactory;
    }

    public function parse(
        ApiRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $geoDistanceParameters = $this->geoDistanceParametersFactory->fromApiRequest($request);

        if ($geoDistanceParameters === null) {
            return $organizerQueryBuilder;
        }

        return $organizerQueryBuilder->withGeoDistanceFilter($geoDistanceParameters);
    }
}

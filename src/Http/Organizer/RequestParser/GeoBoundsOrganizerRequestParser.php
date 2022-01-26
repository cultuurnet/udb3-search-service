<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\GeoBoundsParametersFactory;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class GeoBoundsOrganizerRequestParser implements OrganizerRequestParser
{
    private GeoBoundsParametersFactory $geoBoundsParametersFactory;

    public function __construct(GeoBoundsParametersFactory $geoBoundsParametersFactory)
    {
        $this->geoBoundsParametersFactory = $geoBoundsParametersFactory;
    }

    public function parse(
        ApiRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $geoBoundsParameters = $this->geoBoundsParametersFactory->fromApiRequest($request);

        if ($geoBoundsParameters === null) {
            return $organizerQueryBuilder;
        }

        return $organizerQueryBuilder->withGeoBoundsFilter($geoBoundsParameters);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\Cdbid;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class IdRequestParser implements OrganizerRequestParser
{
    public function parse(
        ApiRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();
        $cdbid = $parameterBagReader->getStringFromParameter('id');

        if (!is_null($cdbid)) {
            $organizerQueryBuilder = $organizerQueryBuilder->withCdbIdFilter(new Cdbid($cdbid));
        }

        return $organizerQueryBuilder;
    }
}

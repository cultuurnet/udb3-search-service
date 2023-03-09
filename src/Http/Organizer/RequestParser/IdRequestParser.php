<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class IdRequestParser implements OrganizerRequestParser
{
    public function parse(
        ApiRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();
        $organizerId = $parameterBagReader->getStringFromParameter('id');

        if (!is_null($organizerId)) {
            $organizerQueryBuilder = $organizerQueryBuilder->withIdFilter($organizerId);
        }

        return $organizerQueryBuilder;
    }
}

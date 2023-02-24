<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class ContributorsRequestParser implements OrganizerRequestParser
{
    public function parse(
        ApiRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();
        $contributor = $parameterBagReader->getStringFromParameter('contributors');

        if (!is_null($contributor)) {
            $organizerQueryBuilder = $organizerQueryBuilder->withContributorsFilter($contributor);
        }

        return $organizerQueryBuilder;
    }
}

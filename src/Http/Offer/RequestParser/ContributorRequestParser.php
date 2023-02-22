<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class ContributorRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();
        $contributor = $parameterBagReader->getStringFromParameter('contributor');

        if (!is_null($contributor)) {
            $offerQueryBuilder = $offerQueryBuilder->withContributorFilter($contributor);
        }
        $offerQueryBuilder->withAgeRangeFilter();

        return $offerQueryBuilder;
    }
}

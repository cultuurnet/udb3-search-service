<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class BirthdateRangeOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $from = $parameterBagReader->getDateFromParameter('birthdateRangeFrom');
        $to = $parameterBagReader->getDateFromParameter('birthdateRangeTo');

        if ($from === null && $to === null) {
            return $offerQueryBuilder;
        }

        return $offerQueryBuilder->withBirthdateRangeFilter($from, $to);
    }
}

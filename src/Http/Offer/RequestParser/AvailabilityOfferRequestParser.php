<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use DateTimeImmutable;

final class AvailabilityOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(ApiRequestInterface $request, OfferQueryBuilderInterface $offerQueryBuilder): OfferQueryBuilderInterface
    {
        $parameterBagReader = $request->getQueryParameterBag();

        $default = DateTimeImmutable::createFromFormat('U', $request->getServerParam('REQUEST_TIME'))
            ->format(DATE_ATOM);

        $availableFrom = $parameterBagReader->getDateTimeFromParameter('availableFrom', $default);
        $availableTo = $parameterBagReader->getDateTimeFromParameter('availableTo', $default);

        // The defaults can still be overwritten with availableFrom=* and/or availableTo=* and/or
        // disableDefaultFilters=true, so only apply the filter if there is actually an availableFrom or availableTo to
        // filter on.
        if ($availableFrom || $availableTo) {
            $offerQueryBuilder = $offerQueryBuilder->withAvailableRangeFilter($availableFrom, $availableTo);
        }

        return $offerQueryBuilder;
    }
}
